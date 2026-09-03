<?php

namespace App\Survey\Infrastructure\Persistence\Doctrine\Repository;

use App\Survey\Application\Query\SurveyQueryInterface;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\QuestionPositionConflictException;
use App\Survey\Domain\Repository\SurveyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Survey>
 */
class DoctrineSurveyRepository extends ServiceEntityRepository implements SurveyRepositoryInterface, SurveyQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    public function findActiveByOwnerPaginated(int $ownerId, int $limit, int $offset): array
    {
        return $this->findBy(
            ['active' => true, 'ownerId' => $ownerId],
            ['id' => 'DESC'],
            $limit,
            $offset,
        );
    }

    public function countActiveByOwner(int $ownerId): int
    {
        return $this->count(['active' => true, 'ownerId' => $ownerId]);
    }

    public function findActiveById(int $id): ?Survey
    {
        return $this->findOneBy([
            'id' => $id,
            'active' => true,
        ]);
    }

    public function findActiveByQuestionId(int $questionId): ?Survey
    {
        return $this->createQueryBuilder('survey')
            ->innerJoin('survey.questions', 'question')
            ->andWhere('survey.active = :active')
            ->andWhere('question.id = :questionId')
            ->setParameter('active', true)
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Survey $survey): void
    {
        try {
            $this->getEntityManager()->persist($survey);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new QuestionPositionConflictException(
                'Position is already used in this survey.',
                previous: $exception,
            );
        }
    }
}
