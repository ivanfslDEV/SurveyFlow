<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidSurveyDataException;
use App\Survey\Domain\Exception\InvalidSubmissionDataException;
use App\Survey\Domain\Exception\QuestionNotFoundException;
use App\Survey\Domain\Exception\QuestionPositionConflictException;
use App\Survey\Domain\Exception\SurveyNotEditableException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Exception\SurveyNotAcceptingSubmissionsException;
use App\Survey\Domain\ValueObject\QuestionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Survey
{
    private ?int $id = null;
    private string $title;
    private ?string $description;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private bool $active;
    private int $ownerId;
    private SurveyStatus $status;

    /**
     * @var Collection<int, Question>
     */
    private Collection $questions;

    private function __construct(
        string $title,
        ?string $description,
        SurveyStatus $status,
        \DateTimeImmutable $createdAt,
        int $ownerId,
    ) {
        self::assertTitle($title);
        self::assertStatusAvailable($status);
        self::assertOwnerId($ownerId);

        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->active = true;
        $this->ownerId = $ownerId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
        $this->questions = new ArrayCollection();
    }

    public static function create(
        string $title,
        ?string $description,
        SurveyStatus $status,
        \DateTimeImmutable $createdAt,
        int $ownerId,
    ): self {
        return new self($title, $description, $status, $createdAt, $ownerId);
    }

    public function updateDetails(
        ?string $title,
        ?string $description,
        bool $updateTitle,
        bool $updateDescription,
        \DateTimeImmutable $updatedAt,
    ): void {
        $this->assertEditable();

        if ($updateTitle) {
            if ($title === null) {
                throw new InvalidSurveyDataException('Title cannot be null.');
            }

            self::assertTitle($title);
            $this->title = $title;
        }

        if ($updateDescription) {
            $this->description = $description;
        }

        $this->updatedAt = $updatedAt;
    }

    public function changeStatus(SurveyStatus $status, \DateTimeImmutable $updatedAt): void
    {
        $this->assertActive();
        self::assertStatusAvailable($status);

        if ($status->isPublished()) {
            $this->assertReadyForPublication();
        }

        $this->status = $status;
        $this->updatedAt = $updatedAt;
    }

    public function deactivate(\DateTimeImmutable $updatedAt): void
    {
        $this->assertActive();
        $this->active = false;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param array<int, array{title: string, type: QuestionType, required: bool, position: int, options?: array<int, array{label: string, position: int}>}> $questionData
     *
     * @return Question[]
     */
    public function addQuestions(array $questionData, \DateTimeImmutable $createdAt): array
    {
        $this->assertEditable();

        $positions = array_column($questionData, 'position');

        if (count($positions) !== count(array_unique($positions))) {
            throw new QuestionPositionConflictException(
                'Question positions must be unique within a survey.',
            );
        }

        foreach ($positions as $position) {
            $this->assertPositionAvailable($position);
        }

        $questions = [];

        foreach ($questionData as $data) {
            $questions[] = Question::create(
                $this,
                $data['title'],
                $data['type'],
                $data['required'],
                $data['position'],
                $createdAt,
                $data['options'] ?? [],
            );
        }

        foreach ($questions as $question) {
            $this->questions->add($question);
        }

        $this->updatedAt = $createdAt;

        return $questions;
    }

    public function updateQuestion(
        int $questionId,
        ?string $title,
        ?QuestionType $type,
        ?bool $required,
        ?int $position,
        bool $updateTitle,
        bool $updateType,
        bool $updateRequired,
        bool $updatePosition,
        \DateTimeImmutable $updatedAt,
        ?array $options = null,
        bool $updateOptions = false,
    ): Question {
        $this->assertEditable();
        $question = $this->findQuestion($questionId);

        if ($updatePosition && $position !== null && $position !== $question->getPosition()) {
            $this->assertPositionAvailable($position);
        }

        $question->update(
            $title,
            $type,
            $required,
            $position,
            $updateTitle,
            $updateType,
            $updateRequired,
            $updatePosition,
            $options,
            $updateOptions,
        );
        $this->updatedAt = $updatedAt;

        return $question;
    }

    /**
     * @param array<int, array{questionId: int, value: mixed}> $answerData
     */
    public function createSubmission(array $answerData, \DateTimeImmutable $createdAt): Submission
    {
        $this->assertAcceptingSubmissions();
        $surveyId = $this->id;

        if ($surveyId === null) {
            throw new InvalidSubmissionDataException(
                'Survey must be persisted before receiving submissions.',
            );
        }

        $questionsById = [];

        foreach ($this->questions as $question) {
            $questionId = $question->getId();

            if ($questionId !== null) {
                $questionsById[$questionId] = $question;
            }
        }

        $answeredQuestionIds = [];
        $normalizedAnswers = [];

        foreach ($answerData as $data) {
            if (!isset($data['questionId'])
                || !is_int($data['questionId'])
                || !array_key_exists('value', $data)) {
                throw new InvalidSubmissionDataException(
                    'Each answer must contain an integer questionId and a value.',
                );
            }

            $questionId = $data['questionId'];

            if (in_array($questionId, $answeredQuestionIds, true)) {
                throw new InvalidSubmissionDataException(
                    'A question can only be answered once per submission.',
                );
            }

            $question = $questionsById[$questionId] ?? null;

            if ($question === null) {
                throw new InvalidSubmissionDataException(
                    'Answered question does not belong to this survey.',
                );
            }

            $answeredQuestionIds[] = $questionId;
            $normalizedAnswers[] = [
                'question' => $question,
                'value' => $question->normalizeAnswer($data['value']),
            ];
        }

        foreach ($questionsById as $questionId => $question) {
            if ($question->isRequired() && !in_array($questionId, $answeredQuestionIds, true)) {
                throw new InvalidSubmissionDataException(
                    sprintf('Question %d is required.', $questionId),
                );
            }
        }

        return Submission::create($surveyId, $normalizedAnswers, $createdAt);
    }

    public function removeQuestion(int $questionId, \DateTimeImmutable $updatedAt): void
    {
        $this->assertEditable();
        $this->questions->removeElement($this->findQuestion($questionId));
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->ownerId === $userId;
    }

    public function getStatus(): SurveyStatus
    {
        return $this->status;
    }

    public function isArchived(): bool
    {
        return $this->status->isArchived();
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function assertAcceptingSubmissions(): void
    {
        $this->assertActive();

        if (!$this->isPublished()) {
            throw new SurveyNotAcceptingSubmissionsException();
        }

        $this->assertReadyForPublication();
    }

    public function assertActive(): void
    {
        if (!$this->active) {
            throw new SurveyNotFoundException();
        }
    }

    public function assertEditable(): void
    {
        $this->assertActive();

        if ($this->isArchived()) {
            throw new SurveyNotEditableException();
        }
    }

    private function findQuestion(int $questionId): Question
    {
        foreach ($this->questions as $question) {
            if ($question->getId() === $questionId) {
                return $question;
            }
        }

        throw new QuestionNotFoundException();
    }

    private function assertPositionAvailable(int $position): void
    {
        foreach ($this->questions as $question) {
            if ($question->getPosition() === $position) {
                throw new QuestionPositionConflictException(
                    'Position is already used in this survey.',
                );
            }
        }
    }

    private static function assertTitle(string $title): void
    {
        if (trim($title) === '') {
            throw new InvalidSurveyDataException('Title cannot be blank.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidSurveyDataException('Title cannot be longer than 255 characters.');
        }
    }

    private static function assertStatusAvailable(SurveyStatus $status): void
    {
        if (!$status->isActive()) {
            throw new InvalidSurveyDataException('Status inactive.');
        }
    }

    private static function assertOwnerId(int $ownerId): void
    {
        if ($ownerId < 1) {
            throw new InvalidSurveyDataException('Owner ID must be greater than zero.');
        }
    }

    private function assertReadyForPublication(): void
    {
        if ($this->questions->isEmpty()) {
            throw new SurveyNotAcceptingSubmissionsException(
                'Survey must contain at least one question before publication.',
            );
        }

        foreach ($this->questions as $question) {
            $question->assertReadyForPublication();
        }
    }
}
