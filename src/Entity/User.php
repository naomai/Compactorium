<?php

namespace Naomai\Compactorium\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    public string $email;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    public string $username;

    #[ORM\Column(type: 'string')]
    private string $password;

    /**
     * @var string[] JSON-encoded array of role strings
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(name: 'registered_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $registeredAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $lastLoginAt = null;

    /** @var bool Stub for future email verification. */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $verified = false;

    /** @var string|null Stub for future email verification token. */
    #[ORM\Column(name: 'verification_token', type: 'string', length: 64, nullable: true)]
    public ?string $verificationToken = null;

    public function __construct() {
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getUserIdentifier(): string {
        return $this->email;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void {
        $this->roles = $roles;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    public function eraseCredentials(): void {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}