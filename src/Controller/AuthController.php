<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register_form', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid form token.');
                return $this->redirectToRoute('app_register');
            }

            $username = trim((string) $request->request->get('username'));
            $password = (string) $request->request->get('password');

            if ($username === '' || $password === '') {
                $this->addFlash('error', 'Username and password are required.');
                return $this->redirectToRoute('app_register');
            }

            if ($users->findOneBy(['username' => $username]) !== null) {
                $this->addFlash('error', 'Username already exists.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setUsername($username);
            $user->setPasswordHash($passwordHasher->hashPassword($user, $password));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Account created. You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/api/protected', name: 'api_protected_example', methods: ['GET'])]
    public function protectedExample(): JsonResponse
    {
        return $this->json([
            'message' => 'Protected route reached.',
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);
    }
}
