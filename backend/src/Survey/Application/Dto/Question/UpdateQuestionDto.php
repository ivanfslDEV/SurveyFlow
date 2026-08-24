<?php

namespace App\Survey\Application\Dto\Question;

use App\Survey\Domain\ValueObject\QuestionType;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateQuestionDto
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $title = null;

    #[Assert\Choice(choices: QuestionType::VALUES, message: 'Invalid question type.')]
    public ?string $type = null;

    public ?bool $required = null;

    #[Assert\Positive(message: 'Position must be greater than zero.')]
    public ?int $position = null;
}
