<?php

namespace App\Tests\Unit;

use App\Security\PasskeyChallengeStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PasskeyChallengeStoreTest extends TestCase
{
    public function testIssuedChallengeCanBeConsumedOnlyOnce(): void
    {
        $store = new PasskeyChallengeStore(new ArrayAdapter(), 300);

        $challenge = $store->issue('user');

        self::assertNotSame('', $challenge);
        self::assertSame($challenge, $store->consume('user'));
        self::assertNull($store->consume('user'));
    }

    public function testChallengeKeyIsCaseInsensitiveForUsername(): void
    {
        $store = new PasskeyChallengeStore(new ArrayAdapter(), 300);

        $challenge = $store->issue('UserName');

        self::assertSame($challenge, $store->consume(' username '));
    }

    public function testConsumeReturnsNullForUnknownUsername(): void
    {
        $store = new PasskeyChallengeStore(new ArrayAdapter(), 300);

        self::assertNull($store->consume('missing-user'));
    }

    public function testStoreCanOverridePreviousChallenge(): void
    {
        $store = new PasskeyChallengeStore(new ArrayAdapter(), 300);

        $store->store('user', 'old-challenge');
        $store->store('user', 'new-challenge');

        self::assertSame('new-challenge', $store->consume('user'));
    }
}
