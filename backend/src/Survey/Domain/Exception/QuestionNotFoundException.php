<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

class QuestionNotFoundException extends ResourceNotFoundException
{
    public function __construct()
    {
        parent::__construct('Question not found.');
    }
}
