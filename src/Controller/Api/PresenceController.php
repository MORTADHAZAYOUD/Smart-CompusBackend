<?php
// src/Controller/PresenceController.php
namespace App\Controller\Api;

use App\Entity\Presence;
use App\Repository\PresenceRepository;
use App\Repository\SeanceRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/presences')]
class PresenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PresenceRepository $presenceRepository,
        private SeanceRepository $seanceRepository,
        private StudentRepository $studentRepository
    ) {}

    #[Route('/seance/{seanceId}', methods: ['GET'])]
    #[OA\Tag(name: 'presence')]
    public function getPresencesBySeance(int $seanceId): JsonResponse
    {
        $presences = $this->presenceRepository->findBy(['seance' => $seanceId]);
        return $this->json($presences, 200, [], ['groups' => ['presence:read']]);
    }

    #[Route('/student/{studentId}', methods: ['GET'])]
    #[OA\Tag(name: 'presence')]
    public function getPresencesByStudent(int $studentId): JsonResponse
    {
        $presences = $this->presenceRepository->findBy(['student' => $studentId]);
        return $this->json($presences, 200, [], ['groups' => ['presence:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'presence')]
    public function markPresence(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $seance = $this->seanceRepository->find($data['seanceId']);
        $student = $this->studentRepository->find($data['studentId']);
        
        // Vérifier si la présence existe déjà
        $presence = $this->presenceRepository->findOneBy([
            'seance' => $seance,
            'student' => $student
        ]);

        if (!$presence) {
            $presence = new Presence();
            $presence->setSeance($seance)
                    ->setStudent($student);
            $this->em->persist($presence);
        }

        $presence->setPresent($data['present']);
        $this->em->flush();

        return $this->json($presence, 200, [], ['groups' => ['presence:read']]);
    }

    #[Route('/bulk', methods: ['POST'])]
    #[OA\Tag(name: 'presence')]
    public function markBulkPresence(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $seanceId = $data['seanceId'];
        $presences = $data['presences']; // [{'studentId': 1, 'present': true}, ...]

        $seance = $this->seanceRepository->find($seanceId);
        
        foreach ($presences as $presenceData) {
            $student = $this->studentRepository->find($presenceData['studentId']);
            
            $presence = $this->presenceRepository->findOneBy([
                'seance' => $seance,
                'student' => $student
            ]);

            if (!$presence) {
                $presence = new Presence();
                $presence->setSeance($seance)
                        ->setStudent($student);
                $this->em->persist($presence);
            }

            $presence->setPresent($presenceData['present']);
        }

        $this->em->flush();

        return $this->json(['message' => 'Présences mises à jour'], 200);
    }
}