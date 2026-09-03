<?php

namespace App\Tests\Unit\Survey\Domain\Entity;

use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Entity\SurveyStatus;
use App\Survey\Domain\Exception\InvalidSurveyDataException;
use App\Survey\Domain\Exception\QuestionPositionConflictException;
use App\Survey\Domain\Exception\SurveyNotEditableException;
use App\Survey\Domain\Exception\SurveyNotFoundException;
use App\Survey\Domain\ValueObject\QuestionType;
use PHPUnit\Framework\TestCase;

final class SurveyTest extends TestCase
{
    private const NOW = '2026-09-03 10:00:00';

    public function testItCreatesAnActiveSurvey(): void
    {
        $createdAt = new \DateTimeImmutable(self::NOW);
        $status = SurveyStatus::create('draft');

        $survey = Survey::create('Customer satisfaction', 'Quarterly survey', $status, $createdAt, 1);

        self::assertNull($survey->getId());
        self::assertSame('Customer satisfaction', $survey->getTitle());
        self::assertSame('Quarterly survey', $survey->getDescription());
        self::assertSame($status, $survey->getStatus());
        self::assertSame($createdAt, $survey->getCreatedAt());
        self::assertSame($createdAt, $survey->getUpdatedAt());
        self::assertTrue($survey->isActive());
        self::assertFalse($survey->isArchived());
        self::assertSame(1, $survey->getOwnerId());
        self::assertTrue($survey->isOwnedBy(1));
        self::assertFalse($survey->isOwnedBy(2));
    }

    public function testItAddsQuestionsThroughTheAggregateRoot(): void
    {
        $createdAt = new \DateTimeImmutable(self::NOW);
        $survey = $this->createDraftSurvey($createdAt);

        $questions = $survey->addQuestions([
            [
                'title' => 'What is your name?',
                'type' => QuestionType::TEXT,
                'required' => true,
                'position' => 1,
            ],
            [
                'title' => 'How do you rate the service?',
                'type' => QuestionType::RATING,
                'required' => false,
                'position' => 2,
            ],
        ], $createdAt);

        self::assertCount(2, $questions);
        self::assertSame($survey, $questions[0]->getSurvey());
        self::assertSame(QuestionType::TEXT, $questions[0]->getType());
        self::assertSame(2, $questions[1]->getPosition());
    }

    public function testItRejectsDuplicateQuestionPositionsInTheSameRequest(): void
    {
        $survey = $this->createDraftSurvey();

        $this->expectException(QuestionPositionConflictException::class);

        $survey->addQuestions([
            [
                'title' => 'First question',
                'type' => QuestionType::TEXT,
                'required' => true,
                'position' => 1,
            ],
            [
                'title' => 'Second question',
                'type' => QuestionType::RATING,
                'required' => false,
                'position' => 1,
            ],
        ], new \DateTimeImmutable(self::NOW));
    }

    public function testItRejectsAPositionAlreadyUsedByTheSurvey(): void
    {
        $survey = $this->createDraftSurvey();
        $createdAt = new \DateTimeImmutable(self::NOW);

        $survey->addQuestions([[
            'title' => 'Existing question',
            'type' => QuestionType::TEXT,
            'required' => true,
            'position' => 1,
        ]], $createdAt);

        $this->expectException(QuestionPositionConflictException::class);

        $survey->addQuestions([[
            'title' => 'Conflicting question',
            'type' => QuestionType::RATING,
            'required' => false,
            'position' => 1,
        ]], $createdAt);
    }

    public function testArchivedSurveyCannotBeEdited(): void
    {
        $survey = Survey::create(
            'Archived survey',
            null,
            SurveyStatus::create(SurveyStatus::ARCHIVED),
            new \DateTimeImmutable(self::NOW),
            1,
        );

        $this->expectException(SurveyNotEditableException::class);

        $survey->updateDetails(
            'Changed title',
            null,
            true,
            false,
            new \DateTimeImmutable('2026-09-03 11:00:00'),
        );
    }

    public function testStatusCanBeChangedFromArchivedToDraft(): void
    {
        $survey = Survey::create(
            'Archived survey',
            null,
            SurveyStatus::create(SurveyStatus::ARCHIVED),
            new \DateTimeImmutable(self::NOW),
            1,
        );
        $draft = SurveyStatus::create('draft');
        $updatedAt = new \DateTimeImmutable('2026-09-03 11:00:00');

        $survey->changeStatus($draft, $updatedAt);

        self::assertSame($draft, $survey->getStatus());
        self::assertFalse($survey->isArchived());
        self::assertSame($updatedAt, $survey->getUpdatedAt());
    }

    public function testInactiveStatusCannotBeAssigned(): void
    {
        $this->expectException(InvalidSurveyDataException::class);

        Survey::create(
            'Survey',
            null,
            SurveyStatus::create('disabled', false),
            new \DateTimeImmutable(self::NOW),
            1,
        );
    }

    public function testDeactivatedSurveyBehavesAsNotFound(): void
    {
        $survey = $this->createDraftSurvey();
        $survey->deactivate(new \DateTimeImmutable('2026-09-03 11:00:00'));

        self::assertFalse($survey->isActive());
        $this->expectException(SurveyNotFoundException::class);

        $survey->updateDetails(
            'Changed title',
            null,
            true,
            false,
            new \DateTimeImmutable('2026-09-03 12:00:00'),
        );
    }

    private function createDraftSurvey(?\DateTimeImmutable $createdAt = null): Survey
    {
        return Survey::create(
            'Survey',
            null,
            SurveyStatus::create('draft'),
            $createdAt ?? new \DateTimeImmutable(self::NOW),
            1,
        );
    }
}
