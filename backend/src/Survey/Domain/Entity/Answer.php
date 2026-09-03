<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidSubmissionDataException;
use App\Survey\Domain\ValueObject\QuestionType;

final class Answer
{
    private ?int $id = null;
    private Submission $submission;
    private int $questionId;
    private string $questionTitle;
    private QuestionType $questionType;
    private mixed $value;

    private function __construct(Submission $submission, Question $question, mixed $value)
    {
        $questionId = $question->getId();

        if ($questionId === null) {
            throw new InvalidSubmissionDataException('Question must be persisted before being answered.');
        }

        $this->submission = $submission;
        $this->questionId = $questionId;
        $this->questionTitle = $question->getTitle();
        $this->questionType = $question->getType();
        $this->value = $value;
    }

    public static function create(Submission $submission, Question $question, mixed $value): self
    {
        return new self($submission, $question, $value);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmission(): Submission
    {
        return $this->submission;
    }

    public function getQuestionId(): int
    {
        return $this->questionId;
    }

    public function getQuestionTitle(): string
    {
        return $this->questionTitle;
    }

    public function getQuestionType(): QuestionType
    {
        return $this->questionType;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
