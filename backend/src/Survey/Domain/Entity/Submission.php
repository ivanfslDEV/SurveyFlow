<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidSubmissionDataException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class Submission
{
    private ?int $id = null;
    private int $surveyId;
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Answer>
     */
    private Collection $answers;

    /**
     * @param array<int, array{question: Question, value: mixed}> $answerData
     */
    private function __construct(
        int $surveyId,
        array $answerData,
        \DateTimeImmutable $createdAt,
    ) {
        if ($surveyId < 1) {
            throw new InvalidSubmissionDataException('Survey ID must be greater than zero.');
        }

        $this->surveyId = $surveyId;
        $this->createdAt = $createdAt;
        $this->answers = new ArrayCollection();

        foreach ($answerData as $data) {
            $this->answers->add(Answer::create($this, $data['question'], $data['value']));
        }
    }

    /**
     * @param array<int, array{question: Question, value: mixed}> $answerData
     */
    public static function create(
        int $surveyId,
        array $answerData,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($surveyId, $answerData, $createdAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurveyId(): int
    {
        return $this->surveyId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Answer[]
     */
    public function getAnswers(): array
    {
        return $this->answers->toArray();
    }
}
