<?php

namespace App\Service;

use App\Dto\Question\CreateQuestionDto;
use App\Entity\Question;
use App\Entity\Survey;
use App\Repository\QuestionRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class QuestionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestionRepository $questionRepository,
        private SurveyService $surveyService,
    ) {
    }

    /**
     * @return Question[]
     */
    public function findBySurvey(Survey $survey, int $limit, int $offset): array
    {
        return $this->questionRepository->findBy(
            ['survey' => $survey],
            ['position' => 'ASC', 'id' => 'ASC'],
            $limit,
            $offset,
        );
    }

    public function countBySurvey(Survey $survey): int
    {
        return $this->questionRepository->count(['survey' => $survey]);
    }

    /**
     * @param CreateQuestionDto[] $dtos
     *
     * @return Question[]
     */
    public function createMany(Survey $survey, array $dtos): array
    {
        $positions = array_map(
            static fn (CreateQuestionDto $dto): int => $dto->position,
            $dtos,
        );

        if (count($positions) !== count(array_unique($positions))) {
            throw new ConflictHttpException('Question positions must be unique within a survey.');
        }

        if ($this->questionRepository->findOneBy([
            'survey' => $survey,
            'position' => $positions,
        ]) !== null) {
            throw new ConflictHttpException('One or more positions are already used in this survey.');
        }

        $questions = [];

        foreach ($dtos as $dto) {
            $question = new Question();
            $question->setSurvey($survey);
            $question->setTitle($dto->title);
            $question->setType($dto->type);
            $question->setRequired($dto->required);
            $question->setPosition($dto->position);
            $question->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($question);
            $questions[] = $question;
        }

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new ConflictHttpException(
                'One or more positions are already used in this survey.',
                previous: $exception,
            );
        }

        return $questions;
    }

    public function findEditable(int $id): Question
    {
        $question = $this->questionRepository->find($id);

        if ($question === null || !$question->getSurvey()->isActive()) {
            throw new NotFoundHttpException('Question not found.');
        }

        $this->surveyService->findEditable($question->getSurvey()->getId());

        return $question;
    }

    public function update(
        Question $question,
        ?string $title,
        ?string $type,
        ?bool $required,
        ?int $position,
        bool $updateTitle,
        bool $updateType,
        bool $updateRequired,
        bool $updatePosition,
    ): Question {
        if ($updateTitle) {
            if ($title === null) {
                throw new UnprocessableEntityHttpException('Title cannot be null.');
            }

            $question->setTitle($title);
        }

        if ($updateType) {
            if ($type === null) {
                throw new UnprocessableEntityHttpException('Type cannot be null.');
            }

            $question->setType($type);
        }

        if ($updateRequired) {
            if ($required === null) {
                throw new UnprocessableEntityHttpException('Required cannot be null.');
            }

            $question->setRequired($required);
        }

        if ($updatePosition) {
            if ($position === null) {
                throw new UnprocessableEntityHttpException('Position cannot be null.');
            }

            $existingQuestion = $this->questionRepository->findOneBy([
                'survey' => $question->getSurvey(),
                'position' => $position,
            ]);

            if ($existingQuestion !== null && $existingQuestion->getId() !== $question->getId()) {
                throw new ConflictHttpException('Position is already used in this survey.');
            }

            $question->setPosition($position);
        }

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new ConflictHttpException(
                'Position is already used in this survey.',
                previous: $exception,
            );
        }

        return $question;
    }

    public function delete(Question $question): void
    {
        $this->entityManager->remove($question);
        $this->entityManager->flush();
    }
}
