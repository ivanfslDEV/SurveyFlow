<?php

namespace App\Survey\Application\Dto\Submission;

use App\Survey\Domain\Entity\Submission;

final class SubmissionResponseDto
{
    /**
     * @param AnswerResponseDto[] $answers
     */
    public function __construct(
        public int $id,
        public int $surveyId,
        public string $createdAt,
        public array $answers,
    ) {
    }

    public static function fromEntity(Submission $submission): self
    {
        return new self(
            id: $submission->getId(),
            surveyId: $submission->getSurveyId(),
            createdAt: $submission->getCreatedAt()->format(\DateTimeInterface::ATOM),
            answers: array_map(
                AnswerResponseDto::fromEntity(...),
                $submission->getAnswers(),
            ),
        );
    }
}
