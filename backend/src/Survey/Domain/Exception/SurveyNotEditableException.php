<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceConflictException;

class SurveyNotEditableException extends ResourceConflictException
{
    public function __construct()
    {
        parent::__construct('Archived surveys cannot be edited.');
    }
}
