<?php

namespace App\Survey\Domain\Repository;

use App\Survey\Domain\Entity\Submission;

interface SubmissionRepositoryInterface
{
    public function findById(int $id): ?Submission;

    public function save(Submission $submission): void;
}
