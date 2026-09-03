<?php

namespace App\Survey\Application\Query;

use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Entity\Survey;

interface QuestionQueryInterface
{
    /**
     * @return Question[]
     */
    public function findBySurvey(Survey $survey): array;

    /**
     * @return Question[]
     */
    public function findBySurveyPaginated(Survey $survey, int $limit, int $offset): array;

    public function countBySurvey(Survey $survey): int;
}
