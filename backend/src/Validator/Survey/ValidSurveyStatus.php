<?php

namespace App\Validator\Survey;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidSurveyStatus extends Constraint
{
    public string $notFoundMessage = 'Status not found.';
    public string $inactiveMessage = 'Status inactive';
}
