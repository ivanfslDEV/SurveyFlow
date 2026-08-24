<?php

namespace App\Survey\Application\Query;

use App\Survey\Domain\Entity\Survey;

interface SurveyQueryInterface
{
    /**
     * @return Survey[]
     */
    public function findActivePaginated(int $limit, int $offset): array;

    public function countActive(): int;
}
