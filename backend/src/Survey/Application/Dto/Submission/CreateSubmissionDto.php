<?php

namespace App\Survey\Application\Dto\Submission;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateSubmissionDto
{
    /**
     * @var array<int, array{questionId: int, value: mixed}>
     */
    #[Assert\Type('array')]
    public array $answers = [];
}
