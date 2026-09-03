<?php

namespace App\Survey\Domain\Exception;

use App\Shared\Domain\Exception\ResourceForbiddenException;

final class SurveyAccessDeniedException extends ResourceForbiddenException
{
    public function __construct()
    {
        parent::__construct('You are not allowed to access this survey.');
    }
}
