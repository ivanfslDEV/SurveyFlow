<?php

namespace App\Survey\Domain\Repository;

use App\Survey\Domain\Entity\SurveyStatus;

interface SurveyStatusRepositoryInterface
{
    public function findOneByName(string $name): ?SurveyStatus;
}
