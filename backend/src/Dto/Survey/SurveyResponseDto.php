<?php
namespace App\Dto\Survey;

use App\Entity\Survey;

class SurveyResponseDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public string $status,
        public bool $active,
    ) {
    }

    public static function fromEntity(Survey $survey): self
    {
        return new self(
            id: $survey->getId(),
            title: $survey->getTitle(),
            description: $survey->getDescription(),
            status: $survey->getStatus()->getName(),
            active: $survey->isActive(),
        );
    }
}
