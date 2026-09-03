<?php

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\ResourceConflictException;

final class EmailAlreadyInUseException extends ResourceConflictException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Email is already in use.', previous: $previous);
    }
}
