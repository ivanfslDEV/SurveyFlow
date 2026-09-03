<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceConflictException;

final class SurveyNotAcceptingSubmissionsException extends ResourceConflictException
{
    public function __construct(string $message = 'Survey is not accepting submissions.')
    {
        parent::__construct($message);
    }
}
