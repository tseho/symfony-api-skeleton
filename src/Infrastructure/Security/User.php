<?php

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $email;
    private ?string $password;
    /**
     * @var string[]
     */
    private array $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(string $email, ?string $password, array $roles)
    {
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @deprecated
     */
    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }
}
