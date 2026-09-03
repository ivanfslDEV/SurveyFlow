<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidQuestionDataException;

final class QuestionOption
{
    private ?int $id = null;
    private Question $question;
    private string $label;
    private int $position;

    private function __construct(Question $question, string $label, int $position)
    {
        $label = trim($label);

        if ($label === '') {
            throw new InvalidQuestionDataException('Option label cannot be blank.');
        }

        if (mb_strlen($label) > 255) {
            throw new InvalidQuestionDataException('Option label cannot be longer than 255 characters.');
        }

        if ($position < 1) {
            throw new InvalidQuestionDataException('Option position must be greater than zero.');
        }

        $this->question = $question;
        $this->label = $label;
        $this->position = $position;
    }

    public static function create(Question $question, string $label, int $position): self
    {
        return new self($question, $label, $position);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
