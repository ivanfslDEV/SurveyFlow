<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class GetSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
    ) {
    }

    public function execute(int $id): Survey
    {
        return $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();
    }
}
