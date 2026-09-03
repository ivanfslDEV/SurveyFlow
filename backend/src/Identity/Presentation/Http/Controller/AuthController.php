<?php

namespace App\Identity\Presentation\Http\Controller;

use App\Identity\Application\Dto\RegisterUserDto;
use App\Identity\Application\Dto\UserResponseDto;
use App\Identity\Application\UseCase\GetCurrentUserUseCase;
use App\Identity\Application\UseCase\RegisterUserUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_auth_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterUserDto $dto,
        RegisterUserUseCase $registerUser,
    ): JsonResponse {
        $user = $registerUser->execute($dto->email, $dto->password);

        return $this->json(
            UserResponseDto::fromEntity($user),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/me', name: 'app_auth_me', methods: ['GET'])]
    public function me(GetCurrentUserUseCase $getCurrentUser): JsonResponse
    {
        return $this->json(UserResponseDto::fromEntity($getCurrentUser->execute()));
    }
}
