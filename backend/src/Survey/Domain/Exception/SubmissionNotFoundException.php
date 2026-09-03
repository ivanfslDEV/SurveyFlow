<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

final class SubmissionNotFoundException extends ResourceNotFoundException
{
    public function __construct()
    {
        parent::__construct('Submission not found.');
    }
}
