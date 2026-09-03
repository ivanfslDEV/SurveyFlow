<?php

namespace App\Survey\Application\Dto\Question;

use App\Survey\Domain\Entity\Question;

class QuestionResponseDto
{
    public function __construct(
        public int $id,
        public int $surveyId,
        public string $title,
        public string $type,
        public bool $required,
        public int $position,
        public string $createdAt,
        public array $options,
    ) {
    }

    public static function fromEntity(Question $question): self
    {
        return new self(
            id: $question->getId(),
            surveyId: $question->getSurvey()->getId(),
            title: $question->getTitle(),
            type: $question->getType()->value,
            required: $question->isRequired(),
            position: $question->getPosition(),
            createdAt: $question->getCreatedAt()->format(\DateTimeInterface::ATOM),
            options: array_map(
                QuestionOptionResponseDto::fromEntity(...),
                $question->getOptions(),
            ),
        );
    }
}
