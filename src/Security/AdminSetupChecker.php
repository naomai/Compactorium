<?php

namespace Naomai\Compactorium\Security;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\User;

/**
 * Detects whether the seeded admin account still needs to be configured.
 * The admin is seeded (migration 010) with an empty password; a real
 * password hash is stored only after onboarding completes.
 */
final class AdminSetupChecker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * True when the seeded admin account has an empty (unset) password.
     */
    public function needsSetup(): bool
    {
        $admin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);

        if ($admin === null) {
            return false;
        }

        return $admin->getPassword() === '';
    }
}
