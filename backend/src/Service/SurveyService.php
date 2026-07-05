<?php

namespace App\Service;

use App\Entity\Survey;
use App\Repository\SurveyRepository;
use App\Repository\SurveyStatusRepository;
use Doctrine\ORM\EntityManagerInterface;

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
        return $this->surveyRepository->findAll();
    }

    public function create(string $title, ?string $description, string $statusName): Survey
    {
        $status = $this->surveyStatusRepository->findOneBy(['name' => $statusName]);

        $survey = new Survey();
        $survey->setTitle($title);
        $survey->setDescription($description);
        $survey->setStatus($status);
        $survey->setCreatedAt(new \DateTimeImmutable());
        $survey->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return $survey;
    }
}
