<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\User;
use Naomai\Compactorium\Views\UserView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $request->getPayload();

        $email = $payload->getString('email');
        $username = $payload->getString('username', '');
        $password = $payload->getString('password');

        if (empty($email) || empty($password)) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null) {
            return $this->json(['error' => 'A user with this email already exists.'], Response::HTTP_CONFLICT);
        }

        if ($username !== '') {
            $existingUsername = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUsername !== null) {
                return $this->json(['error' => 'A user with this username already exists.'], Response::HTTP_CONFLICT);
            }
        }

        $user = new User();
        $user->email = $email;
        $user->username = $username !== '' ? $username : $email;
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(UserView::fromUser($user), Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(UserView::fromUser($user));
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse {
        return $this->json(UserView::fromUser($user));
    } 

    #[Route('/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        return $this->json(UserView::fromUser($user));
    } 
}