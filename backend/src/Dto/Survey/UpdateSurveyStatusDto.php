<?php

namespace App\Dto\Survey;

use App\Validator\Survey\ValidSurveyStatus;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateSurveyStatusDto
{
    #[Assert\NotNull(message: 'Status is mandatory')]
    #[ValidSurveyStatus]
    public string $statusName;
}
