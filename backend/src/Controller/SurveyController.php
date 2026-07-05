<?php

namespace App\Controller;

use App\Dto\Survey\CreateSurveyDto;
use App\Dto\Survey\SurveyResponseDto;
use App\Entity\Survey;
use App\Form\SurveyType;
use App\Repository\SurveyRepository;
use App\Service\SurveyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/survey')]
final class SurveyController extends AbstractController
{
    public function __construct(
        private SurveyService $surveyService,
    ){
    }

    #[Route(name: 'app_survey_index', methods: ['GET'])]
    public function index(SurveyRepository $surveyRepository): Response
    {
        return $this->render('survey/index.html.twig', [
            'surveys' => $surveyRepository->findAll(),
        ]);
    }

    #[Route('/new', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateSurveyDto $dto
    ): JsonResponse
    {
        $survey = $this->surveyService->create($dto->title, $dto->description, $dto->statusName);

        return $this->json(SurveyResponseDto::fromEntity($survey), 201);
    }

    #[Route('/{id}', name: 'app_survey_show', methods: ['GET'])]
    public function show(Survey $survey): Response
    {
        return $this->render('survey/show.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_survey_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Survey $survey, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SurveyType::class, $survey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_survey_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('survey/edit.html.twig', [
            'survey' => $survey,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_survey_delete', methods: ['POST'])]
    public function delete(Request $request, Survey $survey, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$survey->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($survey);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_survey_index', [], Response::HTTP_SEE_OTHER);
    }
}
