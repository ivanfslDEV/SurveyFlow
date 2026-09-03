<?php

namespace App\Survey\Application\UseCase\Submission;

use App\Survey\Application\Query\QuestionQueryInterface;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;

final class GetPublishedSurveyUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private QuestionQueryInterface $questionQuery,
    ) {
    }

    /**
     * @return array{survey: Survey, questions: Question[]}
     */
    public function execute(int $surveyId): array
    {
        $survey = $this->surveyRepository->findPublishedById($surveyId)
            ?? throw new SurveyNotFoundException();
        $survey->assertAcceptingSubmissions();

        return [
            'survey' => $survey,
            'questions' => $this->questionQuery->findBySurvey($survey),
        ];
    }
}
