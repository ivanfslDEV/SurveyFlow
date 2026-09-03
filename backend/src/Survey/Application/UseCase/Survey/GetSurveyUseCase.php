<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use App\Survey\Application\Security\SurveyAccessPolicy;

class GetSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    public function execute(int $id): Survey
    {
        $survey = $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();

        $this->accessPolicy->assertCanManage($survey);

        return $survey;
    }
}
