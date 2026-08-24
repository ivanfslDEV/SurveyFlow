<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class DeleteSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
    ) {
    }

    public function execute(int $id): void
    {
        $survey = $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();

        $survey->deactivate($this->clock->now());
        $this->surveyRepository->save($survey);
    }
}
