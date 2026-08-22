<?php

namespace App\Service;

use App\Entity\Survey;
use App\Entity\SurveyStatus;
use App\Repository\SurveyRepository;
use App\Repository\SurveyStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SurveyService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SurveyRepository $surveyRepository,
        private SurveyStatusRepository $surveyStatusRepository,
    ) {
    }

    public function findAll(): array
    {
        return $this->surveyRepository->findBy(['active' => true]);
    }

    public function findActive(int $id): Survey
    {
        $survey = $this->surveyRepository->findOneBy([
            'id' => $id,
            'active' => true,
        ]);

        if ($survey === null) {
            throw new NotFoundHttpException('Survey not found.');
        }

        return $survey;
    }

    public function findEditable(int $id): Survey
    {
        $survey = $this->findActive($id);
        $this->assertEditable($survey);

        return $survey;
    }

    public function create(string $title, ?string $description, string $statusName): Survey
    {
        $status = $this->surveyStatusRepository->findOneBy(['name' => $statusName]);

        $survey = new Survey();
        $survey->setTitle($title);
        $survey->setDescription($description);
        $survey->setStatus($status);
        $survey->setActive(true);
        $survey->setCreatedAt(new \DateTimeImmutable());
        $survey->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return $survey;
    }

    public function update(
        Survey $survey,
        ?string $title,
        ?string $description,
        bool $updateTitle,
        bool $updateDescription,
    ): Survey {
        $this->assertEditable($survey);

        if ($updateTitle) {
            if ($title === null) {
                throw new UnprocessableEntityHttpException('Title cannot be null.');
            }

            $survey->setTitle($title);
        }

        if ($updateDescription) {
            $survey->setDescription($description);
        }

        $survey->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $survey;
    }

    public function updateStatus(Survey $survey, string $statusName): Survey
    {
        $this->assertActive($survey);

        $status = $this->surveyStatusRepository->findOneBy(['name' => $statusName]);
        $survey->setStatus($status);
        $survey->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $survey;
    }

    public function delete(Survey $survey): void
    {
        $this->assertActive($survey);

        $survey->setActive(false);
        $survey->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    private function assertEditable(Survey $survey): void
    {
        $this->assertActive($survey);

        if (strtolower($survey->getStatus()->getName()) === SurveyStatus::ARCHIVED) {
            throw new ConflictHttpException('Archived surveys cannot be edited.');
        }
    }

    private function assertActive(Survey $survey): void
    {
        if (!$survey->isActive()) {
            throw new NotFoundHttpException('Survey not found.');
        }
    }
}
