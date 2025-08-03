<?php
// src/Controller/NoteController.php
namespace App\Controller\Api;

use App\Entity\Note;
use App\Repository\NoteRepository;
use App\Repository\SeanceRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notes')]
class NoteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NoteRepository $noteRepository,
        private SeanceRepository $seanceRepository,
        private StudentRepository $studentRepository
    ) {}

    #[Route('/student/{studentId}', methods: ['GET'])]
    #[OA\Tag(name: 'note')]
    public function getNotesByStudent(int $studentId): JsonResponse
    {
        $notes = $this->noteRepository->findBy(['student' => $studentId]);
        return $this->json($notes, 200, [], ['groups' => ['note:read']]);
    }

    #[Route('/seance/{seanceId}', methods: ['GET'])]
    #[OA\Tag(name: 'note')]
    public function getNotesBySeance(int $seanceId): JsonResponse
    {
        $notes = $this->noteRepository->findBy(['seance' => $seanceId]);
        return $this->json($notes, 200, [], ['groups' => ['note:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'note')]
    public function createNote(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $note = new Note();
        $note->setValeur($data['valeur']);
        
        $student = $this->studentRepository->find($data['studentId']);
        $seance = $this->seanceRepository->find($data['seanceId']);
        
        $note->setStudent($student)
             ->setSeance($seance);

        $this->em->persist($note);
        $this->em->flush();

        return $this->json($note, 201, [], ['groups' => ['note:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'note')]
    public function updateNote(int $id, Request $request): JsonResponse
    {
        $note = $this->noteRepository->find($id);
        if (!$note) {
            throw $this->createNotFoundException('Note non trouvée');
        }

        $data = json_decode($request->getContent(), true);
        $note->setValeur($data['valeur']);

        $this->em->flush();

        return $this->json($note, 200, [], ['groups' => ['note:read']]);
    }
}
