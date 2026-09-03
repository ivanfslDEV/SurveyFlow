<?php

namespace App\Survey\Application\Dto\Question;

use App\Survey\Domain\Entity\QuestionOption;

final class QuestionOptionResponseDto
{
    public function __construct(
        public int $id,
        public string $label,
        public int $position,
    ) {
    }

    public static function fromEntity(QuestionOption $option): self
    {
        return new self(
            id: $option->getId(),
            label: $option->getLabel(),
            position: $option->getPosition(),
        );
    }
}
