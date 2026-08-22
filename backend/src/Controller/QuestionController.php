<?php

namespace App\Controller;

use App\Dto\Question\CreateQuestionDto;
use App\Dto\Question\QuestionResponseDto;
use App\Dto\Question\UpdateQuestionDto;
use App\Entity\Question;
use App\Service\QuestionService;
use App\Service\SurveyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class QuestionController extends AbstractController
{
    public function __construct(
        private QuestionService $questionService,
        private SurveyService $surveyService,
    ) {
    }

    #[Route('/surveys/{id}/questions', name: 'app_question_create', methods: ['POST'])]
    public function create(
        int $id,
        #[MapRequestPayload(type: CreateQuestionDto::class)] array $dtos,
        ValidatorInterface $validator,
    ): JsonResponse {
        $context = $validator->startContext();
        $context->validate(
            $dtos,
            new Assert\Count(min: 1, minMessage: 'At least one question must be provided.'),
        );

        foreach ($dtos as $index => $dto) {
            $context->atPath("[$index]")->validate($dto);
        }

        $violations = $context->getViolations();

        if (count($violations) > 0) {
            throw new UnprocessableEntityHttpException(
                'Validation Failed',
                new ValidationFailedException($dtos, $violations),
            );
        }

        $survey = $this->surveyService->findEditable($id);
        $questions = array_map(
            static fn (Question $question): QuestionResponseDto => QuestionResponseDto::fromEntity($question),
            $this->questionService->createMany($survey, $dtos),
        );

        return $this->json($questions, Response::HTTP_CREATED);
    }

    #[Route('/surveys/{id}/questions', name: 'app_question_index', methods: ['GET'])]
    public function index(int $id, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $survey = $this->surveyService->findActive($id);
        $total = $this->questionService->countBySurvey($survey);

        $questions = array_map(
            static fn (Question $question): QuestionResponseDto => QuestionResponseDto::fromEntity($question),
            $this->questionService->findBySurvey(
                $survey,
                $limit,
                ($page - 1) * $limit,
            ),
        );

        return $this->json([
            'data' => $questions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/questions/{id}', name: 'app_question_update', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        #[MapRequestPayload] UpdateQuestionDto $dto,
    ): JsonResponse {
        $payload = $request->toArray();
        $updatableFields = array_intersect(
            ['title', 'type', 'required', 'position'],
            array_keys($payload),
        );

        if ($updatableFields === []) {
            throw new UnprocessableEntityHttpException('At least one field must be provided.');
        }

        $question = $this->questionService->update(
            question: $this->questionService->findEditable($id),
            title: $dto->title,
            type: $dto->type,
            required: $dto->required,
            position: $dto->position,
            updateTitle: array_key_exists('title', $payload),
            updateType: array_key_exists('type', $payload),
            updateRequired: array_key_exists('required', $payload),
            updatePosition: array_key_exists('position', $payload),
        );

        return $this->json(QuestionResponseDto::fromEntity($question));
    }

    #[Route('/questions/{id}', name: 'app_question_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->questionService->delete($this->questionService->findEditable($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
