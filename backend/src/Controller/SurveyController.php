<?php

namespace App\Controller;

use App\Dto\Survey\CreateSurveyDto;
use App\Dto\Survey\SurveyResponseDto;
use App\Dto\Survey\UpdateSurveyDto;
use App\Dto\Survey\UpdateSurveyStatusDto;
use App\Entity\Survey;
use App\Repository\SurveyRepository;
use App\Service\SurveyService;
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
        private SurveyService $surveyService,
    ){
    }

    #[Route(name: 'app_survey_index', methods: ['GET'])]
    public function index(Request $request, SurveyRepository $surveyRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $total = $surveyRepository->count(['active' => true]);

        $surveys = array_map(
            static fn (Survey $survey): SurveyResponseDto => SurveyResponseDto::fromEntity($survey),
            $surveyRepository->findBy(
                criteria: ['active' => true],
                orderBy: ['id' => 'DESC'],
                limit: $limit,
                offset: ($page - 1) * $limit,
            ),
        );

        return $this->json([
            'data' => $surveys,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route(methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateSurveyDto $dto
    ): JsonResponse
    {
        $survey = $this->surveyService->create($dto->title, $dto->description, $dto->statusName);

        return $this->json(SurveyResponseDto::fromEntity($survey), 201);
    }

    #[Route('/{id}', name: 'app_survey_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $survey = $this->surveyService->findActive($id);

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

        $survey = $this->surveyService->update(
            survey: $this->surveyService->findActive($id),
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
        $survey = $this->surveyService->updateStatus(
            $this->surveyService->findActive($id),
            $dto->statusName,
        );

        return $this->json(SurveyResponseDto::fromEntity($survey));
    }

    #[Route('/{id}', name: 'app_survey_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->surveyService->delete($this->surveyService->findActive($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
