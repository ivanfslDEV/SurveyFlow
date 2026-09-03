<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class DeleteSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    public function execute(int $id): void
    {
        $survey = $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();

        $this->accessPolicy->assertCanManage($survey);

        $survey->deactivate($this->clock->now());
        $this->surveyRepository->save($survey);
    }
}
