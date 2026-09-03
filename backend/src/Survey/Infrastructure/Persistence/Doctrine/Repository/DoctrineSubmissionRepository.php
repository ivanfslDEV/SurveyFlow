<?php

namespace App\Survey\Infrastructure\Persistence\Doctrine\Repository;

use App\Survey\Application\Query\SubmissionQueryInterface;
use App\Survey\Domain\Entity\Submission;
use App\Survey\Domain\Repository\SubmissionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Submission>
 */
final class DoctrineSubmissionRepository extends ServiceEntityRepository implements SubmissionRepositoryInterface, SubmissionQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function findById(int $id): ?Submission
    {
        return $this->find($id);
    }

    public function save(Submission $submission): void
    {
        $this->getEntityManager()->persist($submission);
        $this->getEntityManager()->flush();
    }

    public function findBySurveyIdPaginated(int $surveyId, int $limit, int $offset): array
    {
        return $this->findBy(
            ['surveyId' => $surveyId],
            ['id' => 'DESC'],
            $limit,
            $offset,
        );
    }

    public function countBySurveyId(int $surveyId): int
    {
        return $this->count(['surveyId' => $surveyId]);
    }
}
