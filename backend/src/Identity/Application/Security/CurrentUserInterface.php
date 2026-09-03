<?php

namespace App\Identity\Application\Security;

interface CurrentUserInterface
{
    public function id(): int;
}
