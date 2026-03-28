<?php

namespace App\Security;

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

class WebAuthnServer
{
    public function __construct(
        private readonly string $passkeyRpId,
        private readonly string $passkeyRpName,
    ) {
    }

    /**
     * @param string[] $credentialIds Base64url credential ids
     *
     * @return array{challenge: string, publicKey: array<string, mixed>}
     */
    public function createAuthenticationOptions(array $credentialIds): array
    {
        $webauthn = $this->createWebAuthn();

        $binaryCredentialIds = [];
        foreach ($credentialIds as $credentialId) {
            if (!is_string($credentialId) || $credentialId === '') {
                continue;
            }

            $binaryCredentialIds[] = $this->base64UrlDecode($credentialId);
        }

        $args = $webauthn->getGetArgs(
            $binaryCredentialIds,
            timeout: 60,
            allowUsb: true,
            allowNfc: true,
            allowBle: true,
            allowHybrid: true,
            allowInternal: true,
            requireUserVerification: 'required'
        );

        return [
            'challenge' => $this->base64UrlEncode($webauthn->getChallenge()->getBinaryString()),
            'publicKey' => $this->normalizePublicKey($args->publicKey),
        ];
    }

    /**
     * @param string[] $excludeCredentialIds Base64url credential ids
     *
     * @return array{challenge: string, publicKey: array<string, mixed>}
     */
    public function createRegistrationOptions(
        string $userId,
        string $username,
        string $displayName,
        array $excludeCredentialIds
    ): array {
        $webauthn = $this->createWebAuthn();

        $binaryExcludeIds = [];
        foreach ($excludeCredentialIds as $credentialId) {
            if (!is_string($credentialId) || $credentialId === '') {
                continue;
            }

            $binaryExcludeIds[] = $this->base64UrlDecode($credentialId);
        }

        $args = $webauthn->getCreateArgs(
            $userId,
            $username,
            $displayName,
            timeout: 60,
            requireResidentKey: false,
            requireUserVerification: 'required',
            crossPlatformAttachment: null,
            excludeCredentialIds: $binaryExcludeIds
        );

        return [
            'challenge' => $this->base64UrlEncode($webauthn->getChallenge()->getBinaryString()),
            'publicKey' => $this->normalizePublicKey($args->publicKey),
        ];
    }

    /**
     * @return array{credentialId: string, publicKeyPem: string, signCount: int}
     */
    public function verifyRegistration(
        string $expectedChallenge,
        string $clientDataJSON,
        string $attestationObject
    ): array {
        $webauthn = $this->createWebAuthn();

        try {
            $result = $webauthn->processCreate(
                clientDataJSON: $this->base64UrlDecode($clientDataJSON),
                attestationObject: $this->base64UrlDecode($attestationObject),
                challenge: $this->base64UrlDecode($expectedChallenge),
                requireUserVerification: true,
                requireUserPresent: true,
                failIfRootMismatch: false,
                requireCtsProfileMatch: false
            );
        } catch (WebAuthnException $e) {
            throw new \RuntimeException('Passkey registration verification failed: ' . $e->getMessage());
        }

        if (!isset($result->credentialId) || !isset($result->credentialPublicKey)) {
            throw new \RuntimeException('Passkey registration returned incomplete credential data.');
        }

        return [
            'credentialId' => $this->base64UrlEncode((string) $result->credentialId),
            'publicKeyPem' => (string) $result->credentialPublicKey,
            'signCount' => is_int($result->signatureCounter ?? null) ? (int) $result->signatureCounter : 0,
        ];
    }

    public function verifyAssertion(
        string $credentialPublicKeyPem,
        string $expectedChallenge,
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        ?int $previousSignCount
    ): int {
        $webauthn = $this->createWebAuthn();

        try {
            $webauthn->processGet(
                clientDataJSON: $this->base64UrlDecode($clientDataJSON),
                authenticatorData: $this->base64UrlDecode($authenticatorData),
                signature: $this->base64UrlDecode($signature),
                credentialPublicKey: $credentialPublicKeyPem,
                challenge: $this->base64UrlDecode($expectedChallenge),
                prevSignatureCnt: $previousSignCount,
                requireUserVerification: true,
                requireUserPresent: true
            );
        } catch (WebAuthnException $e) {
            throw new \RuntimeException('Passkey assertion verification failed: ' . $e->getMessage());
        }

        return $webauthn->getSignatureCounter() ?? ($previousSignCount ?? 0);
    }

    private function createWebAuthn(): WebAuthn
    {
        return new WebAuthn($this->passkeyRpName, $this->passkeyRpId, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePublicKey(object $publicKey): array
    {
        $json = json_encode($publicKey);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode WebAuthn public key options.');
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Unable to decode WebAuthn public key options.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64url payload.');
        }

        return $decoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
