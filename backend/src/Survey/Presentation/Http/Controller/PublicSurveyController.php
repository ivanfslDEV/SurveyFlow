<?php

namespace App\Survey\Presentation\Http\Controller;

use App\Survey\Application\Dto\Submission\CreateSubmissionDto;
use App\Survey\Application\Dto\Submission\SubmissionResponseDto;
use App\Survey\Application\Dto\Survey\PublicSurveyResponseDto;
use App\Survey\Application\UseCase\Submission\CreateSubmissionUseCase;
use App\Survey\Application\UseCase\Submission\GetPublishedSurveyUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/surveys')]
final class PublicSurveyController extends AbstractController
{
    #[Route('/{id}', name: 'app_public_survey_show', methods: ['GET'])]
    public function show(int $id, GetPublishedSurveyUseCase $getPublishedSurvey): JsonResponse
    {
        $result = $getPublishedSurvey->execute($id);

        return $this->json(PublicSurveyResponseDto::fromEntities(
            $result['survey'],
            $result['questions'],
        ));
    }

    #[Route('/{id}/submissions', name: 'app_public_submission_create', methods: ['POST'])]
    public function submit(
        int $id,
        #[MapRequestPayload] CreateSubmissionDto $dto,
        CreateSubmissionUseCase $createSubmission,
    ): JsonResponse {
        $submission = $createSubmission->execute($id, $dto->answers);

        return $this->json(
            SubmissionResponseDto::fromEntity($submission),
            Response::HTTP_CREATED,
        );
    }
}
