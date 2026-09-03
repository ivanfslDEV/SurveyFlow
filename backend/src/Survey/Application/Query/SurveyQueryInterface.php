<?php

namespace App\Survey\Application\Query;

use App\Survey\Domain\Entity\Survey;

interface SurveyQueryInterface
{
    /**
     * @return Survey[]
     */
    public function findActiveByOwnerPaginated(int $ownerId, int $limit, int $offset): array;

    public function countActiveByOwner(int $ownerId): int;
}
