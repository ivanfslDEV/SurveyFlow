<?php

namespace App\Dto\Question;

use App\Entity\Question;
use Symfony\Component\Validator\Constraints as Assert;

class CreateQuestionDto
{
    #[Assert\NotBlank(message: 'Title is mandatory')]
    #[Assert\Length(max: 255)]
    public string $title;

    #[Assert\NotBlank(message: 'Type is mandatory')]
    #[Assert\Choice(choices: Question::VALID_TYPES, message: 'Invalid question type.')]
    public string $type;

    public bool $required = false;

    #[Assert\NotNull(message: 'Position is mandatory')]
    #[Assert\Positive(message: 'Position must be greater than zero.')]
    public int $position;
}
