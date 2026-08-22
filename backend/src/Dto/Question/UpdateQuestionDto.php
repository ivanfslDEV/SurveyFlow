<?php

namespace App\Dto\Question;

use App\Entity\Question;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateQuestionDto
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $title = null;

    #[Assert\Choice(choices: Question::VALID_TYPES, message: 'Invalid question type.')]
    public ?string $type = null;

    public ?bool $required = null;

    #[Assert\Positive(message: 'Position must be greater than zero.')]
    public ?int $position = null;
}
