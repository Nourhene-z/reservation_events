<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly JwtTokenService $jwtTokenService)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?JsonResponse
    {
        $user = $token->getUser();

        return new JsonResponse([
            'token' => $this->jwtTokenService->createToken($user),
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'expires_in' => (int) ($_ENV['JWT_TTL'] ?? 3600),
        ]);
    }
}
