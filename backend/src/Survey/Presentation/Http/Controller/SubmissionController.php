<?php

namespace App\Survey\Presentation\Http\Controller;

use App\Survey\Application\Dto\Submission\SubmissionResponseDto;
use App\Survey\Application\UseCase\Submission\GetSubmissionUseCase;
use App\Survey\Application\UseCase\Submission\ListSubmissionsUseCase;
use App\Survey\Domain\Entity\Submission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class SubmissionController extends AbstractController
{
    #[Route('/surveys/{id}/submissions', name: 'app_submission_index', methods: ['GET'])]
    public function index(
        int $id,
        Request $request,
        ListSubmissionsUseCase $listSubmissions,
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $result = $listSubmissions->execute($id, $limit, ($page - 1) * $limit);

        return $this->json([
            'data' => array_map(
                static fn (Submission $submission): SubmissionResponseDto =>
                    SubmissionResponseDto::fromEntity($submission),
                $result['items'],
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    #[Route('/submissions/{id}', name: 'app_submission_show', methods: ['GET'])]
    public function show(int $id, GetSubmissionUseCase $getSubmission): JsonResponse
    {
        return $this->json(SubmissionResponseDto::fromEntity($getSubmission->execute($id)));
    }
}
