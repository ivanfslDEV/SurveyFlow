<?php

namespace App\Survey\Application\Dto\Survey;

use App\Survey\Application\Validator\Survey\ValidSurveyStatus;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateSurveyStatusDto
{
    #[Assert\NotNull(message: 'Status is mandatory')]
    #[ValidSurveyStatus]
    public string $statusName;
}
