<?php

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Security\CurrentUserInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;

final class GetCurrentUserUseCase
{
    public function __construct(
        private CurrentUserInterface $currentUser,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(): User
    {
        return $this->userRepository->findById($this->currentUser->id())
            ?? throw new UserNotFoundException();
    }
}
