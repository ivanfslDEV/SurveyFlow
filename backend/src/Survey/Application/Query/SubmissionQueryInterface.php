<?php

namespace App\Survey\Application\Query;

use App\Survey\Domain\Entity\Submission;

interface SubmissionQueryInterface
{
    /**
     * @return Submission[]
     */
    public function findBySurveyIdPaginated(int $surveyId, int $limit, int $offset): array;

    public function countBySurveyId(int $surveyId): int;
}
