<?php

namespace App\Identity\Application\Dto;

use App\Identity\Domain\Entity\User;

final class UserResponseDto
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $email,
        public array $roles,
        public bool $active,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            roles: $user->getRoles(),
            active: $user->isActive(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
