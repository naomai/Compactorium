<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\User;
use Naomai\Compactorium\Security\AdminSetupChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/setup')]
class SetupController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AdminSetupChecker $adminSetupChecker,
    ) {
    }

    #[Route('', name: 'setup_form', methods: ['GET'])]
    public function form(Request $request): Response
    {
        if (!$this->adminSetupChecker->needsSetup()) {
            return $this->redirect($request->getBaseUrl() . '/');
        }

        return $this->render('setup/form.html.twig');
    }

    #[Route('', name: 'setup_submit', methods: ['POST'])]
    public function submit(Request $request, Security $security): Response
    {
        if (!$this->adminSetupChecker->needsSetup()) {
            return $this->redirect($request->getBaseUrl() . '/');
        }

        $admin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);

        if (!$admin instanceof User) {
            return $this->redirectToRoute('setup_form');
        }

        $payload = $request->request;

        $email = trim((string) $payload->get('email', ''));
        $password = (string) $payload->get('password', '');
        $confirm = (string) $payload->get('confirm_password', '');

        $error = null;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (
            !preg_match('/[a-z]/', $password)   // lowercase required
            || !preg_match('/[A-Z]/', $password)  // uppercase required
            || !preg_match('/\d/', $password)     // digit required
        ) {
            $error = 'Password must contain at least one uppercase letter, one lowercase letter, and one digit.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        }

        if ($error !== null) {
            return $this->render('setup/form.html.twig', [
                'error' => $error,
            ]);
        }

        $admin->email = $email;
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        // Log the admin in immediately and land on the app.
        $security->login($admin);

        return new RedirectResponse($request->getBaseUrl() . '/');
    }
}
