<?php

namespace App\Dto\Survey;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateSurveyDto
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $title = null;

    public ?string $description = null;
}
