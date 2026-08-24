<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidQuestionDataException;
use App\Survey\Domain\ValueObject\QuestionType;

class Question
{
    private ?int $id = null;
    private Survey $survey;
    private string $title;
    private QuestionType $type;
    private bool $required;
    private int $position;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Survey $survey,
        string $title,
        QuestionType $type,
        bool $required,
        int $position,
        \DateTimeImmutable $createdAt,
    ) {
        self::assertTitle($title);
        self::assertPosition($position);

        $this->survey = $survey;
        $this->title = $title;
        $this->type = $type;
        $this->required = $required;
        $this->position = $position;
        $this->createdAt = $createdAt;
    }

    public static function create(
        Survey $survey,
        string $title,
        QuestionType $type,
        bool $required,
        int $position,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($survey, $title, $type, $required, $position, $createdAt);
    }

    public function update(
        ?string $title,
        ?QuestionType $type,
        ?bool $required,
        ?int $position,
        bool $updateTitle,
        bool $updateType,
        bool $updateRequired,
        bool $updatePosition,
    ): void {
        if ($updateTitle) {
            if ($title === null) {
                throw new InvalidQuestionDataException('Title cannot be null.');
            }

            self::assertTitle($title);
        }

        if ($updateType) {
            if ($type === null) {
                throw new InvalidQuestionDataException('Type cannot be null.');
            }
        }

        if ($updateRequired) {
            if ($required === null) {
                throw new InvalidQuestionDataException('Required cannot be null.');
            }
        }

        if ($updatePosition) {
            if ($position === null) {
                throw new InvalidQuestionDataException('Position cannot be null.');
            }

            self::assertPosition($position);
        }

        if ($updateTitle) {
            $this->title = $title;
        }

        if ($updateType) {
            $this->type = $type;
        }

        if ($updateRequired) {
            $this->required = $required;
        }

        if ($updatePosition) {
            $this->position = $position;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): Survey
    {
        return $this->survey;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): QuestionType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function assertTitle(string $title): void
    {
        if (trim($title) === '') {
            throw new InvalidQuestionDataException('Title cannot be blank.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidQuestionDataException('Title cannot be longer than 255 characters.');
        }
    }

    private static function assertPosition(int $position): void
    {
        if ($position < 1) {
            throw new InvalidQuestionDataException('Position must be greater than zero.');
        }
    }
}
