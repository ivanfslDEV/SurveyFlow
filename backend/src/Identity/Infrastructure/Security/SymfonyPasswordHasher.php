<?php

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Security\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(SecurityUser::class)
            ->hash($plainPassword);
    }
}
