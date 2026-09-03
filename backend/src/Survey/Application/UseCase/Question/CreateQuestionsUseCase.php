<?php

namespace App\Survey\Application\UseCase\Question;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Application\Dto\Question\CreateQuestionDto;
use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Exception\InvalidQuestionDataException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use App\Survey\Domain\ValueObject\QuestionType;

class CreateQuestionsUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    /**
     * @param CreateQuestionDto[] $dtos
     *
     * @return Question[]
     */
    public function execute(int $surveyId, array $dtos): array
    {
        $survey = $this->surveyRepository->findActiveById($surveyId)
            ?? throw new SurveyNotFoundException();
        $this->accessPolicy->assertCanManage($survey);
        $questionData = array_map(
            static function (CreateQuestionDto $dto): array {
                $type = QuestionType::tryFrom($dto->type)
                    ?? throw new InvalidQuestionDataException('Invalid question type.');

                return [
                    'title' => $dto->title,
                    'type' => $type,
                    'required' => $dto->required,
                    'position' => $dto->position,
                ];
            },
            $dtos,
        );

        $questions = $survey->addQuestions($questionData, $this->clock->now());
        $this->surveyRepository->save($survey);

        return $questions;
    }
}
