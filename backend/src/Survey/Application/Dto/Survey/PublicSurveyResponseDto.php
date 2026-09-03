<?php

namespace App\Survey\Application\Dto\Survey;

use App\Survey\Application\Dto\Question\QuestionResponseDto;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Entity\Survey;

final class PublicSurveyResponseDto
{
    /**
     * @param QuestionResponseDto[] $questions
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public string $status,
        public array $questions,
    ) {
    }

    /**
     * @param Question[] $questions
     */
    public static function fromEntities(Survey $survey, array $questions): self
    {
        return new self(
            id: $survey->getId(),
            title: $survey->getTitle(),
            description: $survey->getDescription(),
            status: $survey->getStatus()->getName(),
            questions: array_map(QuestionResponseDto::fromEntity(...), $questions),
        );
    }
}
