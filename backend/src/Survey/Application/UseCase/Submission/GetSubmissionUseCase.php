<?php

namespace App\Survey\Application\UseCase\Submission;

use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Entity\Submission;
use App\Survey\Domain\Exception\SubmissionNotFoundException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SubmissionRepositoryInterface;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

final class GetSubmissionUseCase
{
    public function __construct(
        private SubmissionRepositoryInterface $submissionRepository,
        private SurveyRepositoryInterface $surveyRepository,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    public function execute(int $submissionId): Submission
    {
        $submission = $this->submissionRepository->findById($submissionId)
            ?? throw new SubmissionNotFoundException();
        $survey = $this->surveyRepository->findActiveById($submission->getSurveyId())
            ?? throw new SurveyNotFoundException();
        $this->accessPolicy->assertCanManage($survey);

        return $submission;
    }
}
