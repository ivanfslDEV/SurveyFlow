<?php

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

final class UserNotFoundException extends ResourceNotFoundException
{
    public function __construct()
    {
        parent::__construct('User not found.');
    }
}
