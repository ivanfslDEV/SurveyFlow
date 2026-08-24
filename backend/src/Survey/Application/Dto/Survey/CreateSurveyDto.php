<?php

namespace App\Survey\Application\Dto\Survey;

use App\Survey\Application\Validator\Survey\ValidSurveyStatus;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSurveyDto
{
    #[Assert\NotBlank(message: 'Title is mandatory')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    #[Assert\NotNull(message: 'Status is mandatory')]
    #[ValidSurveyStatus]
    public string $statusName;
}
