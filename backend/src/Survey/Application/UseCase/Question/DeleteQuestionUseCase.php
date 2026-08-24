<?php

namespace App\Survey\Application\UseCase\Question;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Domain\Exception\QuestionNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class DeleteQuestionUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
    ) {
    }

    public function execute(int $id): void
    {
        $survey = $this->surveyRepository->findActiveByQuestionId($id)
            ?? throw new QuestionNotFoundException();

        $survey->removeQuestion($id, $this->clock->now());
        $this->surveyRepository->save($survey);
    }
}
