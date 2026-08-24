<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Survey\Application\Query\SurveyQueryInterface;
use App\Survey\Domain\Entity\Survey;

class ListSurveysUseCase
{
    public function __construct(
        private SurveyQueryInterface $surveyQuery,
    ) {
    }

    /**
     * @return array{items: Survey[], total: int}
     */
    public function execute(int $limit, int $offset): array
    {
        return [
            'items' => $this->surveyQuery->findActivePaginated($limit, $offset),
            'total' => $this->surveyQuery->countActive(),
        ];
    }
}
