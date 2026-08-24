<?php

namespace App\Survey\Application\UseCase\Question;

use App\Survey\Application\Query\QuestionQueryInterface;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

class ListQuestionsUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private QuestionQueryInterface $questionQuery,
    ) {
    }

    /**
     * @return array{items: Question[], total: int}
     */
    public function execute(int $surveyId, int $limit, int $offset): array
    {
        $survey = $this->surveyRepository->findActiveById($surveyId)
            ?? throw new SurveyNotFoundException();

        return [
            'items' => $this->questionQuery->findBySurveyPaginated($survey, $limit, $offset),
            'total' => $this->questionQuery->countBySurvey($survey),
        ];
    }
}
