<?php

namespace App\Controller;

use App\Entity\PasskeyCredential;
use App\Entity\User;
use App\Repository\PasskeyCredentialRepository;
use App\Repository\UserRepository;
use App\Security\JwtTokenService;
use App\Security\PasskeyChallengeStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly string $passkeyRpId,
        private readonly string $passkeyRpName,
    ) {
    }

    #[Route('/options', name: 'api_passkey_options', methods: ['POST'])]
    public function options(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $username = trim((string) ($payload['username'] ?? ''));

        if ($username === '') {
            return $this->json(['error' => 'username is required'], 400);
        }

        $user = $this->users->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not found'], 404);
        }

        $credentials = $this->credentials->findByUser('user', $username);
        $allowCredentials = array_map(static function (PasskeyCredential $credential): array {
            return [
                'id' => $credential->getCredentialId(),
                'type' => 'public-key',
                'transports' => $credential->getTransports() ?? [],
            ];
        }, $credentials);

        $challenge = $this->challengeStore->issue($username);

        return $this->json([
            'challenge' => $challenge,
            'rpId' => $this->passkeyRpId,
            'rpName' => $this->passkeyRpName,
            'username' => $username,
            'displayName' => $user->getUsername(),
            'timeout' => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => $allowCredentials,
        ]);
    }

    #[Route('/verify', name: 'api_passkey_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $username = trim((string) ($payload['username'] ?? ''));
        $challenge = (string) ($payload['challenge'] ?? '');
        $credentialId = trim((string) ($payload['credentialId'] ?? ''));

        if ($username === '' || $challenge === '' || $credentialId === '') {
            return $this->json(['error' => 'username, challenge and credentialId are required'], 400);
        }

        $expected = $this->challengeStore->consume($username);
        if ($expected === null || !hash_equals($expected, $challenge)) {
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

        $challenge = $this->challengeStore->issue($user->getUsername());

        return $this->json([
            'challenge' => $challenge,
            'rpId' => $this->passkeyRpId,
            'rpName' => $this->passkeyRpName,
            'username' => $user->getUsername(),
            'displayName' => $user->getUsername(),
            'timeout' => 60000,
            'attestation' => 'none',
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

        $payload = json_decode($request->getContent(), true) ?? [];
        $challenge = (string) ($payload['challenge'] ?? '');
        $credentialId = trim((string) ($payload['credentialId'] ?? ''));
        $publicKey = (string) ($payload['publicKey'] ?? '');
        $transports = $payload['transports'] ?? [];

        if ($credentialId === '' || $challenge === '' || $publicKey === '') {
            return $this->json(['error' => 'credentialId, publicKey and challenge are required'], 400);
        }

        $expected = $this->challengeStore->consume($user->getUsername());
        if ($expected === null || !hash_equals($expected, $challenge)) {
            return $this->json(['error' => 'invalid challenge'], 401);
        }

        if ($this->credentials->findOneByCredentialId($credentialId) instanceof PasskeyCredential) {
            return $this->json(['error' => 'credential already exists'], 409);
        }

        $credential = new PasskeyCredential();
        $credential
            ->setUserType('user')
            ->setUserIdentifier($user->getUsername())
            ->setCredentialId($credentialId)
            ->setPublicKey($publicKey !== '' ? $publicKey : null)
            ->setSignCount(0)
            ->setTransports(is_array($transports) ? $transports : null)
            ->setCreatedAt(new \DateTime());

        $this->em->persist($credential);
        $this->em->flush();

        return $this->json([
            'status' => 'ok',
            'credentialId' => $credentialId,
        ]);
    }
}
