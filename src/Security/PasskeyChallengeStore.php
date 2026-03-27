<?php

namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;

class PasskeyChallengeStore
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $passkeyChallengeTtl,
    ) {
    }

    public function issue(string $username): string
    {
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $item = $this->cache->getItem($this->buildKey($username));
        $item->set($challenge);
        $item->expiresAfter($this->passkeyChallengeTtl);
        $this->cache->save($item);

        return $challenge;
    }

    public function consume(string $username): ?string
    {
        $item = $this->cache->getItem($this->buildKey($username));
        if (!$item->isHit()) {
            return null;
        }

        $challenge = $item->get();
        $this->cache->deleteItem($this->buildKey($username));

        return is_string($challenge) ? $challenge : null;
    }

    private function buildKey(string $username): string
    {
        return 'passkey.challenge.' . hash('sha256', strtolower(trim($username)));
    }
}
