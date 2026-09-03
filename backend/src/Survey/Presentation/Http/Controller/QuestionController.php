<?php

namespace App\Survey\Presentation\Http\Controller;

use App\Survey\Application\Dto\Question\CreateQuestionDto;
use App\Survey\Application\Dto\Question\QuestionResponseDto;
use App\Survey\Application\Dto\Question\UpdateQuestionDto;
use App\Survey\Application\UseCase\Question\CreateQuestionsUseCase;
use App\Survey\Application\UseCase\Question\DeleteQuestionUseCase;
use App\Survey\Application\UseCase\Question\ListQuestionsUseCase;
use App\Survey\Application\UseCase\Question\UpdateQuestionUseCase;
use App\Survey\Domain\Entity\Question;
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
        private CreateQuestionsUseCase $createQuestions,
        private ListQuestionsUseCase $listQuestions,
        private UpdateQuestionUseCase $updateQuestion,
        private DeleteQuestionUseCase $deleteQuestion,
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

        $questions = array_map(
            static fn (Question $question): QuestionResponseDto => QuestionResponseDto::fromEntity($question),
            $this->createQuestions->execute($id, $dtos),
        );

        return $this->json($questions, Response::HTTP_CREATED);
    }

    #[Route('/surveys/{id}/questions', name: 'app_question_index', methods: ['GET'])]
    public function index(int $id, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $result = $this->listQuestions->execute(
            $id,
            $limit,
            ($page - 1) * $limit,
        );

        $questions = array_map(
            static fn (Question $question): QuestionResponseDto => QuestionResponseDto::fromEntity($question),
            $result['items'],
        );

        return $this->json([
            'data' => $questions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $limit),
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
            ['title', 'type', 'required', 'position', 'options'],
            array_keys($payload),
        );

        if ($updatableFields === []) {
            throw new UnprocessableEntityHttpException('At least one field must be provided.');
        }

        $question = $this->updateQuestion->execute(
            id: $id,
            title: $dto->title,
            type: $dto->type,
            required: $dto->required,
            position: $dto->position,
            updateTitle: array_key_exists('title', $payload),
            updateType: array_key_exists('type', $payload),
            updateRequired: array_key_exists('required', $payload),
            updatePosition: array_key_exists('position', $payload),
            options: $dto->options,
            updateOptions: array_key_exists('options', $payload),
        );

        return $this->json(QuestionResponseDto::fromEntity($question));
    }

    #[Route('/questions/{id}', name: 'app_question_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->deleteQuestion->execute($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
