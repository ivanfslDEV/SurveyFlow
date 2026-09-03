<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Identity\Application\Security\CurrentUserInterface;
use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\InvalidSurveyDataException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use App\Survey\Domain\Repository\SurveyStatusRepositoryInterface;

class CreateSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private SurveyStatusRepositoryInterface $surveyStatusRepository,
        private ClockInterface $clock,
        private CurrentUserInterface $currentUser,
    ) {
    }

    public function execute(string $title, ?string $description, string $statusName): Survey
    {
        $status = $this->surveyStatusRepository->findOneByName($statusName)
            ?? throw new InvalidSurveyDataException('Status not found.');

        $survey = Survey::create(
            $title,
            $description,
            $status,
            $this->clock->now(),
            $this->currentUser->id(),
        );

        $this->surveyRepository->save($survey);

        return $survey;
    }
}
