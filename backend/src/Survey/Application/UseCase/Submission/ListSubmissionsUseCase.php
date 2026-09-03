<?php

namespace App\Survey\Application\UseCase\Submission;

use App\Survey\Application\Query\SubmissionQueryInterface;
use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Entity\Submission;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

final class ListSubmissionsUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private SubmissionQueryInterface $submissionQuery,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    /**
     * @return array{items: Submission[], total: int}
     */
    public function execute(int $surveyId, int $limit, int $offset): array
    {
        $survey = $this->surveyRepository->findActiveById($surveyId)
            ?? throw new SurveyNotFoundException();
        $this->accessPolicy->assertCanManage($survey);

        return [
            'items' => $this->submissionQuery->findBySurveyIdPaginated(
                $surveyId,
                $limit,
                $offset,
            ),
            'total' => $this->submissionQuery->countBySurveyId($surveyId),
        ];
    }
}
