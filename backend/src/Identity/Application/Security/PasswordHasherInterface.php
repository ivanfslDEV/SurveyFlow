<?php

namespace App\Identity\Application\Security;

interface PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string;
}
