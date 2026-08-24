<?php

namespace App\Survey\Domain\Repository;

use App\Survey\Domain\Entity\Survey;

interface SurveyRepositoryInterface
{
    public function findActiveById(int $id): ?Survey;

    public function findActiveByQuestionId(int $questionId): ?Survey;

    public function save(Survey $survey): void;
}
