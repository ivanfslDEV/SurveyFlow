<?php

namespace App\Survey\Application\UseCase\Submission;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Domain\Entity\Submission;
use App\Survey\Domain\Exception\InvalidSubmissionDataException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SubmissionRepositoryInterface;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

final class CreateSubmissionUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private SubmissionRepositoryInterface $submissionRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<mixed> $answers
     */
    public function execute(int $surveyId, array $answers): Submission
    {
        $survey = $this->surveyRepository->findPublishedById($surveyId)
            ?? throw new SurveyNotFoundException();
        $answerData = array_map(
            static function (mixed $answer): array {
                if (!is_array($answer)
                    || !isset($answer['questionId'])
                    || !is_int($answer['questionId'])
                    || !array_key_exists('value', $answer)) {
                    throw new InvalidSubmissionDataException(
                        'Each answer must contain an integer questionId and a value.',
                    );
                }

                return [
                    'questionId' => $answer['questionId'],
                    'value' => $answer['value'],
                ];
            },
            $answers,
        );

        $submission = $survey->createSubmission($answerData, $this->clock->now());
        $this->submissionRepository->save($submission);

        return $submission;
    }
}
