<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtTokenService
{
    public function __construct(
        private readonly string $jwtSecret,
        private readonly int $jwtTtl
    ) {
    }

    public function createToken(UserInterface $user): string
    {
        $now = time();

        $payload = [
            'sub' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'iat' => $now,
            'exp' => $now + $this->jwtTtl,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
