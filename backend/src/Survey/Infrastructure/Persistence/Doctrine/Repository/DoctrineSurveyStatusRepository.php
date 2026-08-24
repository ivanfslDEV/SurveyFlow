<?php

namespace App\Survey\Infrastructure\Persistence\Doctrine\Repository;

use App\Survey\Domain\Entity\SurveyStatus;
use App\Survey\Domain\Repository\SurveyStatusRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SurveyStatus>
 */
class DoctrineSurveyStatusRepository extends ServiceEntityRepository implements SurveyStatusRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyStatus::class);
    }

    public function findOneByName(string $name): ?SurveyStatus
    {
        return $this->findOneBy(['name' => $name]);
    }
}
