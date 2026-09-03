<?php

namespace App\Survey\Infrastructure\Persistence\Doctrine\Repository;

use App\Survey\Application\Query\QuestionQueryInterface;
use App\Survey\Domain\Entity\Question;
use App\Survey\Domain\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class DoctrineQuestionQuery extends ServiceEntityRepository implements QuestionQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findBySurvey(Survey $survey): array
    {
        return $this->findBy(
            ['survey' => $survey],
            ['position' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function findBySurveyPaginated(Survey $survey, int $limit, int $offset): array
    {
        return $this->findBy(
            ['survey' => $survey],
            ['position' => 'ASC', 'id' => 'ASC'],
            $limit,
            $offset,
        );
    }

    public function countBySurvey(Survey $survey): int
    {
        return $this->count(['survey' => $survey]);
    }

}
