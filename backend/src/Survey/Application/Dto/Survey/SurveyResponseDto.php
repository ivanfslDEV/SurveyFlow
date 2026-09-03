<?php
namespace App\Survey\Application\Dto\Survey;

use App\Survey\Domain\Entity\Survey;

class SurveyResponseDto
{
    public function __construct(
        public int $id,
        public int $ownerId,
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
            ownerId: $survey->getOwnerId(),
            title: $survey->getTitle(),
            description: $survey->getDescription(),
            status: $survey->getStatus()->getName(),
            active: $survey->isActive(),
        );
    }
}
