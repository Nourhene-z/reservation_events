<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityRoutesTest extends WebTestCase
{
    public function testLoginRouteRedirectsToAdminLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseRedirects('/admin/login');
    }

    public function testAdminLoginPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');

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

    public function testPasskeyOptionsRequiresUsername(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/passkey/options',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );

        self::assertResponseStatusCodeSame(400);
    }
}
