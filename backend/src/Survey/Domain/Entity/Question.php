<?php

namespace App\Survey\Domain\Entity;

use App\Survey\Domain\Exception\InvalidQuestionDataException;
use App\Survey\Domain\Exception\InvalidSubmissionDataException;
use App\Survey\Domain\ValueObject\QuestionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Question
{
    private ?int $id = null;
    private Survey $survey;
    private string $title;
    private QuestionType $type;
    private bool $required;
    private int $position;
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, QuestionOption>
     */
    private Collection $options;

    private function __construct(
        Survey $survey,
        string $title,
        QuestionType $type,
        bool $required,
        int $position,
        \DateTimeImmutable $createdAt,
        array $optionData = [],
    ) {
        self::assertTitle($title);
        self::assertPosition($position);

        $this->survey = $survey;
        $this->title = $title;
        $this->type = $type;
        $this->required = $required;
        $this->position = $position;
        $this->createdAt = $createdAt;
        $this->options = new ArrayCollection();

        foreach ($this->buildOptions($optionData, $type) as $option) {
            $this->options->add($option);
        }
    }

    public static function create(
        Survey $survey,
        string $title,
        QuestionType $type,
        bool $required,
        int $position,
        \DateTimeImmutable $createdAt,
        array $optionData = [],
    ): self {
        return new self($survey, $title, $type, $required, $position, $createdAt, $optionData);
    }

    public function update(
        ?string $title,
        ?QuestionType $type,
        ?bool $required,
        ?int $position,
        bool $updateTitle,
        bool $updateType,
        bool $updateRequired,
        bool $updatePosition,
        ?array $optionData = null,
        bool $updateOptions = false,
    ): void {
        if ($updateTitle) {
            if ($title === null) {
                throw new InvalidQuestionDataException('Title cannot be null.');
            }

            self::assertTitle($title);
        }

        if ($updateType) {
            if ($type === null) {
                throw new InvalidQuestionDataException('Type cannot be null.');
            }
        }

        if ($updateRequired) {
            if ($required === null) {
                throw new InvalidQuestionDataException('Required cannot be null.');
            }
        }

        if ($updatePosition) {
            if ($position === null) {
                throw new InvalidQuestionDataException('Position cannot be null.');
            }

            self::assertPosition($position);
        }

        $effectiveType = $updateType ? $type : $this->type;
        $replacementOptions = null;

        if ($updateOptions) {
            if ($optionData === null) {
                throw new InvalidQuestionDataException('Options cannot be null.');
            }

            $replacementOptions = $this->buildOptions($optionData, $effectiveType);
        } elseif ($updateType) {
            if (self::supportsOptions($effectiveType)) {
                if ($this->options->count() < 2) {
                    throw new InvalidQuestionDataException(
                        'Choice questions must contain at least two options.',
                    );
                }
            } elseif (!$this->options->isEmpty()) {
                $replacementOptions = [];
            }
        }

        if ($updateTitle) {
            $this->title = $title;
        }

        if ($updateType) {
            $this->type = $type;
        }

        if ($updateRequired) {
            $this->required = $required;
        }

        if ($updatePosition) {
            $this->position = $position;
        }

        if ($replacementOptions !== null) {
            $this->options->clear();

            foreach ($replacementOptions as $option) {
                $this->options->add($option);
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): Survey
    {
        return $this->survey;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): QuestionType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return QuestionOption[]
     */
    public function getOptions(): array
    {
        return $this->options->toArray();
    }

    public function assertReadyForPublication(): void
    {
        if (self::supportsOptions($this->type) && $this->options->count() < 2) {
            throw new InvalidQuestionDataException(
                'Choice questions must contain at least two options.',
            );
        }
    }

    public function normalizeAnswer(mixed $value): mixed
    {
        return match ($this->type) {
            QuestionType::TEXT => $this->normalizeTextAnswer($value),
            QuestionType::RATING => $this->normalizeRatingAnswer($value),
            QuestionType::SINGLE_CHOICE => $this->normalizeSingleChoiceAnswer($value),
            QuestionType::MULTIPLE_CHOICE => $this->normalizeMultipleChoiceAnswer($value),
        };
    }

    private static function assertTitle(string $title): void
    {
        if (trim($title) === '') {
            throw new InvalidQuestionDataException('Title cannot be blank.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidQuestionDataException('Title cannot be longer than 255 characters.');
        }
    }

    private static function assertPosition(int $position): void
    {
        if ($position < 1) {
            throw new InvalidQuestionDataException('Position must be greater than zero.');
        }
    }

    /**
     * @param array<int, array{label: string, position: int}> $optionData
     *
     * @return QuestionOption[]
     */
    private function buildOptions(array $optionData, QuestionType $type): array
    {
        if (!self::supportsOptions($type)) {
            if ($optionData !== []) {
                throw new InvalidQuestionDataException(
                    'Only choice questions can contain options.',
                );
            }

            return [];
        }

        if (count($optionData) < 2) {
            throw new InvalidQuestionDataException(
                'Choice questions must contain at least two options.',
            );
        }

        $positions = [];
        $labels = [];
        $options = [];

        foreach ($optionData as $data) {
            if (!isset($data['label'], $data['position'])
                || !is_string($data['label'])
                || !is_int($data['position'])) {
                throw new InvalidQuestionDataException('Each option must contain a label and position.');
            }

            $normalizedLabel = mb_strtolower(trim($data['label']));

            if (in_array($data['position'], $positions, true)) {
                throw new InvalidQuestionDataException('Option positions must be unique.');
            }

            if (in_array($normalizedLabel, $labels, true)) {
                throw new InvalidQuestionDataException('Option labels must be unique.');
            }

            $positions[] = $data['position'];
            $labels[] = $normalizedLabel;
            $options[] = QuestionOption::create($this, $data['label'], $data['position']);
        }

        usort(
            $options,
            static fn (QuestionOption $left, QuestionOption $right): int =>
                $left->getPosition() <=> $right->getPosition(),
        );

        return $options;
    }

    private static function supportsOptions(QuestionType $type): bool
    {
        return $type === QuestionType::SINGLE_CHOICE
            || $type === QuestionType::MULTIPLE_CHOICE;
    }

    private function normalizeTextAnswer(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidSubmissionDataException('Text answer cannot be blank.');
        }

        return trim($value);
    }

    private function normalizeRatingAnswer(mixed $value): int
    {
        if (!is_int($value) || $value < 1 || $value > 5) {
            throw new InvalidSubmissionDataException('Rating answer must be an integer between 1 and 5.');
        }

        return $value;
    }

    /**
     * @return array{optionId: int, label: string}
     */
    private function normalizeSingleChoiceAnswer(mixed $value): array
    {
        if (!is_int($value)) {
            throw new InvalidSubmissionDataException('Single choice answer must be an option ID.');
        }

        return $this->optionSnapshot($value);
    }

    /**
     * @return array<int, array{optionId: int, label: string}>
     */
    private function normalizeMultipleChoiceAnswer(mixed $value): array
    {
        if (!is_array($value) || $value === []) {
            throw new InvalidSubmissionDataException(
                'Multiple choice answer must contain at least one option ID.',
            );
        }

        $optionIds = [];
        $snapshots = [];

        foreach ($value as $optionId) {
            if (!is_int($optionId)) {
                throw new InvalidSubmissionDataException(
                    'Multiple choice answer must contain only option IDs.',
                );
            }

            if (in_array($optionId, $optionIds, true)) {
                throw new InvalidSubmissionDataException(
                    'Multiple choice answer cannot contain duplicate options.',
                );
            }

            $optionIds[] = $optionId;
            $snapshots[] = $this->optionSnapshot($optionId);
        }

        return $snapshots;
    }

    /**
     * @return array{optionId: int, label: string}
     */
    private function optionSnapshot(int $optionId): array
    {
        foreach ($this->options as $option) {
            if ($option->getId() === $optionId) {
                return [
                    'optionId' => $optionId,
                    'label' => $option->getLabel(),
                ];
            }
        }

        throw new InvalidSubmissionDataException('Selected option does not belong to the question.');
    }
}
