<?php

namespace App\Survey\Presentation\Http\Controller;

use App\Survey\Application\Dto\Survey\CreateSurveyDto;
use App\Survey\Application\Dto\Survey\SurveyResponseDto;
use App\Survey\Application\Dto\Survey\UpdateSurveyDto;
use App\Survey\Application\Dto\Survey\UpdateSurveyStatusDto;
use App\Survey\Application\UseCase\Survey\CreateSurveyUseCase;
use App\Survey\Application\UseCase\Survey\DeleteSurveyUseCase;
use App\Survey\Application\UseCase\Survey\GetSurveyUseCase;
use App\Survey\Application\UseCase\Survey\ListSurveysUseCase;
use App\Survey\Application\UseCase\Survey\UpdateSurveyStatusUseCase;
use App\Survey\Application\UseCase\Survey\UpdateSurveyUseCase;
use App\Survey\Domain\Entity\Survey;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/surveys')]
final class SurveyController extends AbstractController
{
    public function __construct(
        private ListSurveysUseCase $listSurveys,
        private GetSurveyUseCase $getSurvey,
        private CreateSurveyUseCase $createSurvey,
        private UpdateSurveyUseCase $updateSurvey,
        private UpdateSurveyStatusUseCase $updateSurveyStatus,
        private DeleteSurveyUseCase $deleteSurvey,
    ) {
    }

    #[Route(name: 'app_survey_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $result = $this->listSurveys->execute(
            $limit,
            ($page - 1) * $limit,
        );

        $surveys = array_map(
            static fn (Survey $survey): SurveyResponseDto => SurveyResponseDto::fromEntity($survey),
            $result['items'],
        );

        return $this->json([
            'data' => $surveys,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    #[Route(name: 'app_survey_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateSurveyDto $dto
    ): JsonResponse
    {
        $survey = $this->createSurvey->execute($dto->title, $dto->description, $dto->statusName);

        return $this->json(SurveyResponseDto::fromEntity($survey), 201);
    }

    #[Route('/{id}', name: 'app_survey_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $survey = $this->getSurvey->execute($id);

        return $this->json(SurveyResponseDto::fromEntity($survey));
    }

    #[Route('/{id}', name: 'app_survey_update', methods: ['PATCH'])]
    public function update(
        Request $request,
        int $id,
        #[MapRequestPayload] UpdateSurveyDto $dto,
    ): JsonResponse
    {
        $payload = $request->toArray();
        $updatableFields = array_intersect(['title', 'description'], array_keys($payload));

        if ($updatableFields === []) {
            throw new UnprocessableEntityHttpException('At least one field must be provided.');
        }

        $survey = $this->updateSurvey->execute(
            id: $id,
            title: $dto->title,
            description: $dto->description,
            updateTitle: array_key_exists('title', $payload),
            updateDescription: array_key_exists('description', $payload),
        );

        return $this->json(SurveyResponseDto::fromEntity($survey));
    }

    #[Route('/{id}/status', name: 'app_survey_update_status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateSurveyStatusDto $dto,
    ): JsonResponse {
        $survey = $this->updateSurveyStatus->execute(
            $id,
            $dto->statusName,
        );

        return $this->json(SurveyResponseDto::fromEntity($survey));
    }

    #[Route('/{id}', name: 'app_survey_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->deleteSurvey->execute($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
