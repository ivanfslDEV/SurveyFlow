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
        ?array $options,
        bool $updateOptions,
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
            $options === null ? null : self::normalizeOptions($options),
            $updateOptions,
        );
        $this->surveyRepository->save($survey);

        return $question;
    }

    /**
     * @param array<mixed> $options
     *
     * @return array<int, array{label: string, position: int}>
     */
    private static function normalizeOptions(array $options): array
    {
        return array_map(
            static function (mixed $option): array {
                if (!is_array($option)
                    || !isset($option['label'], $option['position'])
                    || !is_string($option['label'])
                    || !is_int($option['position'])) {
                    throw new InvalidQuestionDataException(
                        'Each option must contain a label and integer position.',
                    );
                }

                return [
                    'label' => $option['label'],
                    'position' => $option['position'],
                ];
            },
            $options,
        );
    }
}
