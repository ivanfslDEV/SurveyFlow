<?php

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Security\CurrentUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SymfonyCurrentUser implements CurrentUserInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function id(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $user->getId();
    }
}
