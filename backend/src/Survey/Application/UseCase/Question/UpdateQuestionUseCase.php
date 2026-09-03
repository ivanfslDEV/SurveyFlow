<?php

namespace App\Survey\Application\UseCase\Question;

use App\Shared\Domain\Clock\ClockInterface;
use App\Survey\Application\Security\SurveyAccessPolicy;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Exception\InvalidQuestionDataException;
use App\Survey\Domain\Exception\QuestionNotFoundException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use App\Survey\Domain\ValueObject\QuestionType;

class UpdateQuestionUseCase
{
    public function __construct(
        private SurveyRepositoryInterface $surveyRepository,
        private ClockInterface $clock,
        private SurveyAccessPolicy $accessPolicy,
    ) {
    }

    public function execute(
        int $id,
        ?string $title,
        ?string $type,
        ?bool $required,
        ?int $position,
        bool $updateTitle,
        bool $updateType,
        bool $updateRequired,
        bool $updatePosition,
    ): Question {
        $survey = $this->surveyRepository->findActiveByQuestionId($id)
            ?? throw new QuestionNotFoundException();
        $this->accessPolicy->assertCanManage($survey);
        $questionType = $type === null ? null : QuestionType::tryFrom($type);

        if ($updateType && $type !== null && $questionType === null) {
            throw new InvalidQuestionDataException('Invalid question type.');
        }

        $question = $survey->updateQuestion(
            $id,
            $title,
            $questionType,
            $required,
            $position,
            $updateTitle,
            $updateType,
            $updateRequired,
            $updatePosition,
            $this->clock->now(),
        );
        $this->surveyRepository->save($survey);

        return $question;
    }
}
