<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

class SurveyNotFoundException extends ResourceNotFoundException
{
    public function __construct()
    {
        parent::__construct('Survey not found.');
    }
}
