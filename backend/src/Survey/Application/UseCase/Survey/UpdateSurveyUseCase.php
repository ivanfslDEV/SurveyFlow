<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class UpdateSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
    ) {
    }

    public function execute(
        int $id,
        ?string $title,
        ?string $description,
        bool $updateTitle,
        bool $updateDescription,
    ): Survey {
        $survey = $this->surveyRepository->findActiveById($id)
            ?? throw new SurveyNotFoundException();
        $survey->updateDetails(
            $title,
            $description,
            $updateTitle,
            $updateDescription,
            $this->clock->now(),
        );
        $this->surveyRepository->save($survey);

        return $survey;
    }
}
