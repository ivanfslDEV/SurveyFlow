<?php

namespace App\Tests\Unit\Survey\Domain\Entity;

use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Entity\SurveyStatus;
use App\Survey\Domain\Exception\InvalidQuestionDataException;
use App\Survey\Domain\ValueObject\QuestionType;
use PHPUnit\Framework\TestCase;

final class QuestionTest extends TestCase
{
    private Survey $survey;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->createdAt = new \DateTimeImmutable('2026-09-03 10:00:00');
        $this->survey = Survey::create(
            'Survey',
            null,
            SurveyStatus::create('draft'),
            $this->createdAt,
            1,
        );
    }

    public function testItCreatesAQuestion(): void
    {
        $question = Question::create(
            $this->survey,
            'How do you rate the service?',
            QuestionType::RATING,
            true,
            1,
            $this->createdAt,
        );

        self::assertNull($question->getId());
        self::assertSame($this->survey, $question->getSurvey());
        self::assertSame('How do you rate the service?', $question->getTitle());
        self::assertSame(QuestionType::RATING, $question->getType());
        self::assertTrue($question->isRequired());
        self::assertSame(1, $question->getPosition());
        self::assertSame($this->createdAt, $question->getCreatedAt());
    }

    public function testItRejectsABlankTitle(): void
    {
        $this->expectException(InvalidQuestionDataException::class);

        Question::create(
            $this->survey,
            '   ',
            QuestionType::TEXT,
            false,
            1,
            $this->createdAt,
        );
    }

    public function testItRejectsANonPositivePosition(): void
    {
        $this->expectException(InvalidQuestionDataException::class);

        Question::create(
            $this->survey,
            'Question',
            QuestionType::TEXT,
            false,
            0,
            $this->createdAt,
        );
    }

    public function testFailedUpdateDoesNotPartiallyChangeTheQuestion(): void
    {
        $question = Question::create(
            $this->survey,
            'Original title',
            QuestionType::TEXT,
            false,
            1,
            $this->createdAt,
        );

        try {
            $question->update(
                ' ',
                QuestionType::RATING,
                true,
                2,
                true,
                true,
                true,
                true,
            );
            self::fail('An invalid title should throw an exception.');
        } catch (InvalidQuestionDataException) {
            self::assertSame('Original title', $question->getTitle());
            self::assertSame(QuestionType::TEXT, $question->getType());
            self::assertFalse($question->isRequired());
            self::assertSame(1, $question->getPosition());
        }
    }
}
