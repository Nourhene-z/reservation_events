<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityRoutesTest extends WebTestCase
{
    public function testLoginPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testApiMeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRegisterPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
    }

    public function testAdminDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(302);
    }

    public function testProtectedApiRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/protected');

        self::assertResponseStatusCodeSame(401);
    }
}
