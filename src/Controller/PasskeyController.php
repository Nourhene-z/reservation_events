<?php

namespace App\Controller;

use App\Entity\PasskeyCredential;
use App\Entity\User;
use App\Repository\PasskeyCredentialRepository;
use App\Repository\UserRepository;
use App\Security\JwtTokenService;
use App\Security\PasskeyChallengeStore;
use App\Security\WebAuthnServer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/passkey')]
class PasskeyController extends AbstractController
{
    public function __construct(
        private readonly PasskeyChallengeStore $challengeStore,
        private readonly UserRepository $users,
        private readonly PasskeyCredentialRepository $credentials,
        private readonly EntityManagerInterface $em,
        private readonly JwtTokenService $jwt,
        private readonly WebAuthnServer $webAuthn,
        private readonly RateLimiterFactory $passkeyPublicLimiter,
        private readonly RateLimiterFactory $passkeyAuthenticatedLimiter,
    ) {
    }

    #[Route('/options', name: 'api_passkey_options', methods: ['POST'])]
    public function options(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $username = trim((string) ($payload['username'] ?? ''));

        $publicLimit = $this->consumePasskeyRateLimit(
            $this->passkeyPublicLimiter,
            $this->buildPublicKey($request, $username)
        );
        if ($publicLimit !== null) {
            return $publicLimit;
        }

        if ($username === '') {
            return $this->json(['error' => 'username is required'], 400);
        }

        $user = $this->users->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not found'], 404);
        }

        $credentials = $this->credentials->findByUser('user', $username);
        $credentialIds = array_values(array_filter(array_map(
            static fn (PasskeyCredential $credential): ?string => $credential->getCredentialId(),
            $credentials
        )));

        $options = $this->webAuthn->createAuthenticationOptions($credentialIds);
        $challenge = $options['challenge'];
        $this->challengeStore->store($username, $challenge);

        $publicKey = $options['publicKey'];

        return $this->json([
            'publicKey' => $publicKey,
            'challenge' => $challenge,
            'rpId' => $publicKey['rpId'] ?? null,
            'username' => $username,
            'displayName' => $user->getUsername(),
            'timeout' => $publicKey['timeout'] ?? 60000,
            'userVerification' => $publicKey['userVerification'] ?? 'required',
            'allowCredentials' => $publicKey['allowCredentials'] ?? [],
        ]);
    }

    #[Route('/verify', name: 'api_passkey_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $username = trim((string) ($payload['username'] ?? ''));

        $publicLimit = $this->consumePasskeyRateLimit(
            $this->passkeyPublicLimiter,
            $this->buildPublicKey($request, $username)
        );
        if ($publicLimit !== null) {
            return $publicLimit;
        }

        $assertion = is_array($payload['assertion'] ?? null) ? $payload['assertion'] : $payload;
        $response = is_array($assertion['response'] ?? null) ? $assertion['response'] : $payload;
        $credentialId = trim((string) ($assertion['id'] ?? $payload['credentialId'] ?? ''));
        $clientDataJSON = trim((string) ($response['clientDataJSON'] ?? $payload['clientDataJSON'] ?? ''));
        $authenticatorData = trim((string) ($response['authenticatorData'] ?? $payload['authenticatorData'] ?? ''));
        $signature = trim((string) ($response['signature'] ?? $payload['signature'] ?? ''));

        if (
            $username === ''
            || $credentialId === ''
            || $clientDataJSON === ''
            || $authenticatorData === ''
            || $signature === ''
        ) {
            return $this->json(['error' => 'username, credentialId and WebAuthn assertion fields are required'], 400);
        }

        $expected = $this->challengeStore->consume($username);
        if ($expected === null) {
            return $this->json(['error' => 'invalid challenge'], 401);
        }

        $user = $this->users->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not found'], 404);
        }

        $credential = $this->credentials->findOneByCredentialId($credentialId);
        if (!$credential instanceof PasskeyCredential) {
            return $this->json(['error' => 'unknown credential'], 401);
        }

        if ($credential->getUserType() !== 'user' || $credential->getUserIdentifier() !== $username) {
            return $this->json(['error' => 'credential does not belong to user'], 401);
        }

        try {
            $newSignCount = $this->webAuthn->verifyAssertion(
                credentialPublicKeyPem: (string) $credential->getPublicKey(),
                expectedChallenge: $expected,
                clientDataJSON: $clientDataJSON,
                authenticatorData: $authenticatorData,
                signature: $signature,
                previousSignCount: $credential->getSignCount()
            );
        } catch (\RuntimeException) {
            return $this->json(['error' => 'invalid assertion'], 401);
        }

        $credential->setSignCount($newSignCount);
        $this->em->flush();

        return $this->json([
            'token' => $this->jwt->createToken($user),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/register/options', name: 'api_passkey_register_options', methods: ['POST'])]
    public function registerOptions(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'authentication required'], 401);
        }

        $authLimit = $this->consumePasskeyRateLimit(
            $this->passkeyAuthenticatedLimiter,
            $this->buildAuthenticatedKey($request, (string) $user->getUsername())
        );
        if ($authLimit !== null) {
            return $authLimit;
        }

        $credentials = $this->credentials->findByUser('user', $user->getUsername());
        $excludeCredentialIds = array_values(array_filter(array_map(
            static fn (PasskeyCredential $credential): ?string => $credential->getCredentialId(),
            $credentials
        )));

        $options = $this->webAuthn->createRegistrationOptions(
            userId: (string) $user->getId(),
            username: (string) $user->getUsername(),
            displayName: (string) $user->getUsername(),
            excludeCredentialIds: $excludeCredentialIds
        );

        $challenge = $options['challenge'];
        $this->challengeStore->store($user->getUsername(), $challenge);

        $publicKey = $options['publicKey'];

        return $this->json([
            'publicKey' => $publicKey,
            'challenge' => $challenge,
            'rpId' => $publicKey['rp']['id'] ?? null,
            'rpName' => $publicKey['rp']['name'] ?? null,
            'username' => $user->getUsername(),
            'displayName' => $user->getUsername(),
            'timeout' => $publicKey['timeout'] ?? 60000,
            'attestation' => $publicKey['attestation'] ?? 'none',
        ]);
    }

    #[Route('/register/verify', name: 'api_passkey_register_verify', methods: ['POST'])]
    public function registerVerify(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'authentication required'], 401);
        }

        $authLimit = $this->consumePasskeyRateLimit(
            $this->passkeyAuthenticatedLimiter,
            $this->buildAuthenticatedKey($request, (string) $user->getUsername())
        );
        if ($authLimit !== null) {
            return $authLimit;
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $attestation = is_array($payload['attestation'] ?? null) ? $payload['attestation'] : $payload;
        $response = is_array($attestation['response'] ?? null) ? $attestation['response'] : $payload;
        $clientProvidedCredentialId = trim((string) ($attestation['id'] ?? $payload['credentialId'] ?? ''));
        $clientDataJSON = trim((string) ($response['clientDataJSON'] ?? $payload['clientDataJSON'] ?? ''));
        $attestationObject = trim((string) ($response['attestationObject'] ?? $payload['attestationObject'] ?? ''));
        $transports = $payload['transports'] ?? [];

        if ($clientDataJSON === '' || $attestationObject === '') {
            return $this->json(['error' => 'WebAuthn attestation payload is required'], 400);
        }

        $expected = $this->challengeStore->consume($user->getUsername());
        if ($expected === null) {
            return $this->json(['error' => 'invalid challenge'], 401);
        }

        try {
            $verified = $this->webAuthn->verifyRegistration(
                expectedChallenge: $expected,
                clientDataJSON: $clientDataJSON,
                attestationObject: $attestationObject
            );
        } catch (\RuntimeException) {
            return $this->json(['error' => 'invalid attestation'], 401);
        }

        $credentialId = $verified['credentialId'];
        $publicKeyPem = $verified['publicKeyPem'];
        $signCount = $verified['signCount'];

        if ($clientProvidedCredentialId !== '' && $clientProvidedCredentialId !== $credentialId) {
            return $this->json(['error' => 'credential id mismatch'], 400);
        }

        if ($this->credentials->findOneByCredentialId($credentialId) instanceof PasskeyCredential) {
            return $this->json(['error' => 'credential already exists'], 409);
        }

        $credential = new PasskeyCredential();
        $credential
            ->setUserType('user')
            ->setUserIdentifier($user->getUsername())
            ->setCredentialId($credentialId)
            ->setPublicKey($publicKeyPem !== '' ? $publicKeyPem : null)
            ->setSignCount($signCount)
            ->setTransports(is_array($transports) ? $transports : null)
            ->setCreatedAt(new \DateTime());

        $this->em->persist($credential);
        $this->em->flush();

        return $this->json([
            'status' => 'ok',
            'credentialId' => $credentialId,
        ]);
    }

    private function consumePasskeyRateLimit(RateLimiterFactory $factory, string $key): ?JsonResponse
    {
        $limit = $factory->create($key)->consume(1);
        if ($limit->isAccepted()) {
            return null;
        }

        $retryAfter = $limit->getRetryAfter();

        return $this->json([
            'error' => 'too many requests',
            'retry_after' => $retryAfter?->getTimestamp(),
        ], 429);
    }

    private function buildPublicKey(Request $request, string $username): string
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $normalizedUsername = strtolower(trim($username));

        return 'passkey.public.' . $ip . '.' . hash('sha256', $normalizedUsername !== '' ? $normalizedUsername : $ip);
    }

    private function buildAuthenticatedKey(Request $request, string $username): string
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $normalizedUsername = strtolower(trim($username));

        return 'passkey.auth.' . $ip . '.' . hash('sha256', $normalizedUsername !== '' ? $normalizedUsername : $ip);
    }
}
