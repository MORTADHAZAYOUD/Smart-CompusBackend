<?php
namespace App\Controller\Api;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// 2. TeacherController - Gestion des teachers
#[Route('/api/teachers')]
class TeacherController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'teacher')]

    public function index(): JsonResponse
    {
        $teachers = $this->entityManager->getRepository(Teacher::class)->findAll();
        return $this->json($teachers);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'teacher')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $Teacher = new Teacher();
        $Teacher->setSpecialite($data['specialite']);
        
        $this->entityManager->persist($Teacher);
        $this->entityManager->flush();
        
        return $this->json($Teacher, 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'teacher')]
    public function show(Teacher $Teacher): JsonResponse
    {
        return $this->json($Teacher);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'teacher')]
    public function update(Request $request, Teacher $Teacher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $Teacher->setSpecialite($data['specialite'] ?? $Teacher->getSpecialite());
        
        $this->entityManager->flush();
        
        return $this->json($Teacher);
    }

    #[Route('/{id}/seances', methods: ['GET'])]
    #[OA\Tag(name: 'teacher')]
    public function getSeances(Teacher $Teacher): JsonResponse
    {
        return $this->json($Teacher->getSeances());
    }
}