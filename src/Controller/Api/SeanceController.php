<?php
// src/Controller/SeanceController.php
namespace App\Controller\Api;

use App\Entity\Seance;
use App\Repository\SeanceRepository;
use App\Repository\ClasseRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/seances')]
class SeanceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SeanceRepository $seanceRepository,
        private ClasseRepository $classeRepository,
        private TeacherRepository $teacherRepository
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'seance')]
    public function getSeances(Request $request): JsonResponse
    {
        $classeId = $request->query->get('classe');
        $teacherId = $request->query->get('teacher');
        
        if ($classeId) {
            $seances = $this->seanceRepository->findBy(['classe' => $classeId]);
        } elseif ($teacherId) {
            $seances = $this->seanceRepository->findBy(['enseignant' => $teacherId]);
        } else {
            $seances = $this->seanceRepository->findAll();
        }

        return $this->json($seances, 200, [], ['groups' => ['seance:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'seance')]
    public function createSeance(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $seance = new Seance();
        $seance->setType($data['type'])
               ->setDate(new \DateTime($data['date']))
               ->setPresentiel($data['presentiel']);

        if (!$data['presentiel'] && isset($data['visioLink'])) {
            $seance->setVisioLink($data['visioLink']);
        }

        if (isset($data['classeId'])) {
            $classe = $this->classeRepository->find($data['classeId']);
            $seance->setClasse($classe);
        }

        if (isset($data['enseignantId'])) {
            $teacher = $this->teacherRepository->find($data['enseignantId']);
            $seance->setEnseignant($teacher);
        }

        $this->em->persist($seance);
        $this->em->flush();

        return $this->json($seance, 201, [], ['groups' => ['seance:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'seance')]
    public function updateSeance(int $id, Request $request): JsonResponse
    {
        $seance = $this->seanceRepository->find($id);
        if (!$seance) {
            throw $this->createNotFoundException('Séance non trouvée');
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['type'])) $seance->setType($data['type']);
        if (isset($data['date'])) $seance->setDate(new \DateTime($data['date']));
        if (isset($data['presentiel'])) $seance->setPresentiel($data['presentiel']);
        if (isset($data['visioLink'])) $seance->setVisioLink($data['visioLink']);

        $this->em->flush();

        return $this->json($seance, 200, [], ['groups' => ['seance:read']]);
    }
}