<?php

namespace App\Security;

use App\Repository\AdminRepository;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly string $jwtSecret,
        private readonly UserRepository $userRepository,
        private readonly AdminRepository $adminRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with((string) $request->getPathInfo(), '/api/')) {
            return false;
        }

        if ($request->getPathInfo() === '/api/login') {
            return false;
        }

        return str_starts_with((string) $request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        $jwt = substr($authHeader, 7);

        if ($jwt === '') {
            throw new CustomUserMessageAuthenticationException('Missing JWT token.');
        }

        try {
            $payload = JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token.');
        }

        $identifier = (string) ($payload->sub ?? '');

        if ($identifier === '') {
            throw new CustomUserMessageAuthenticationException('Token subject is missing.');
        }

        return new SelfValidatingPassport(new UserBadge($identifier, function (string $id) {
            $user = $this->userRepository->findOneBy(['username' => $id]);
            if ($user !== null) {
                return $user;
            }

            $admin = $this->adminRepository->findOneBy(['username' => $id]);
            if ($admin !== null) {
                return $admin;
            }

            throw new CustomUserMessageAuthenticationException('User not found.');
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'error' => 'Authentication required.',
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
