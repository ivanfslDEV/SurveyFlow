<?php

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Security\PasswordHasherInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Exception\EmailAlreadyInUseException;
use App\Identity\Domain\Exception\InvalidUserDataException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Clock\ClockInterface;

final class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
    ) {
    }

    public function execute(string $email, #[\SensitiveParameter] string $plainPassword): User
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if ($this->userRepository->findByEmail($normalizedEmail) !== null) {
            throw new EmailAlreadyInUseException();
        }

        if (mb_strlen($plainPassword) < 8) {
            throw new InvalidUserDataException('Password must contain at least 8 characters.');
        }

        if (mb_strlen($plainPassword) > 4096) {
            throw new InvalidUserDataException('Password is too long.');
        }

        $user = User::register(
            $normalizedEmail,
            $this->passwordHasher->hash($plainPassword),
            $this->clock->now(),
        );
        $this->userRepository->save($user);

        return $user;
    }
}
