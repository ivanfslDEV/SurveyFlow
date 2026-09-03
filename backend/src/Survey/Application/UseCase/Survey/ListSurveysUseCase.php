<?php

namespace App\Survey\Application\UseCase\Survey;

use App\Identity\Application\Security\CurrentUserInterface;
use App\Survey\Application\Query\SurveyQueryInterface;
use App\Survey\Domain\Entity\Survey;

class ListSurveysUseCase
{
    public function __construct(
        private SurveyQueryInterface $surveyQuery,
        private CurrentUserInterface $currentUser,
    ) {
    }

    /**
     * @return array{items: Survey[], total: int}
     */
    public function execute(int $limit, int $offset): array
    {
        return [
            'items' => $this->surveyQuery->findActiveByOwnerPaginated(
                $this->currentUser->id(),
                $limit,
                $offset,
            ),
            'total' => $this->surveyQuery->countActiveByOwner($this->currentUser->id()),
        ];
    }
}
