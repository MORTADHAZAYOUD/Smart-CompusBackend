<?php
// src/Controller/StatisticsController.php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\StudentRepository;
use App\Repository\PresenceRepository;
use App\Repository\NoteRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/statistics')]
class StatisticsController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private StudentRepository $studentRepository,
        private PresenceRepository $presenceRepository,
        private NoteRepository $noteRepository
    ) {}

    #[Route('/dashboard', methods: ['GET'])]
    #[OA\Tag(name: 'statistics')]
    public function getDashboardStats(): JsonResponse
    {
        $stats = [
            'users' => [
                'total' => $this->userRepository->count([]),
                'students' => $this->userRepository->countByRole('ROLE_STUDENT'),
                'teachers' => $this->userRepository->countByRole('ROLE_TEACHER'),
                'parents' => $this->userRepository->countByRole('ROLE_PARENT'),
                'admins' => $this->userRepository->countByRole('ROLE_ADMIN')
            ],
            'attendance' => [
                'global_rate' => $this->presenceRepository->calculateGlobalAttendanceRate(),
                'by_class' => $this->presenceRepository->getAttendanceRatesByClass()
            ],
            'grades' => [
                'global_average' => $this->noteRepository->calculateGlobalAverage(),
                'by_class' => $this->noteRepository->getAveragesByClass()
            ]
        ];

        return $this->json($stats);
    }

    #[Route('/attendance/{classeId}', methods: ['GET'])]
    #[OA\Tag(name: 'statistics')]
    public function getClassAttendanceStats(int $classeId): JsonResponse
    {
        $stats = $this->presenceRepository->getClassAttendanceStats($classeId);
        return $this->json($stats);
    }

    #[Route('/grades/{classeId}', methods: ['GET'])]
    #[OA\Tag(name: 'statistics')]
    public function getClassGradeStats(int $classeId): JsonResponse
    {
        $stats = $this->noteRepository->getClassGradeStats($classeId);
        return $this->json($stats);
    }
}
