<?php

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<string> $roles
     */
    private function __construct(
        private int $id,
        private string $email,
        private string $passwordHash,
        private array $roles,
    ) {
    }

    public static function fromDomain(User $user): self
    {
        $id = $user->getId();

        if ($id === null) {
            throw new \LogicException('The user must be persisted before authentication.');
        }

        return new self(
            $id,
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getRoles(),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}
