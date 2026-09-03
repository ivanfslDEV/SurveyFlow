<?php

namespace App\Identity\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserDto
{
    #[Assert\NotBlank(message: 'Email is mandatory.')]
    #[Assert\Email(message: 'Email is invalid.')]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank(message: 'Password is mandatory.')]
    #[Assert\Length(
        min: 8,
        max: 4096,
        minMessage: 'Password must contain at least 8 characters.',
        maxMessage: 'Password is too long.',
    )]
    public string $password;
}
