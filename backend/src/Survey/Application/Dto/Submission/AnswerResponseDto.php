<?php

namespace App\Survey\Application\Dto\Submission;

use App\Survey\Domain\Entity\Answer;

final class AnswerResponseDto
{
    public function __construct(
        public int $id,
        public int $questionId,
        public string $questionTitle,
        public string $questionType,
        public mixed $value,
    ) {
    }

    public static function fromEntity(Answer $answer): self
    {
        return new self(
            id: $answer->getId(),
            questionId: $answer->getQuestionId(),
            questionTitle: $answer->getQuestionTitle(),
            questionType: $answer->getQuestionType()->value,
            value: $answer->getValue(),
        );
    }
}
