<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\InvalidSurveyDataException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use App\Survey\Domain\Repository\SurveyStatusRepositoryInterface;

class UpdateSurveyStatusUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private SurveyStatusRepositoryInterface $surveyStatusRepository,
        private ClockInterface $clock,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    public function execute(int $id, string $statusName): Survey
    {
        $survey = $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();
        $this->accessPolicy->assertCanManage($survey);
        $status = $this->surveyStatusRepository->findOneByName($statusName)
            ?? throw new InvalidSurveyDataException('Status not found.');

        $survey->changeStatus($status, $this->clock->now());
        $this->surveyRepository->save($survey);

        return $survey;
    }
}
