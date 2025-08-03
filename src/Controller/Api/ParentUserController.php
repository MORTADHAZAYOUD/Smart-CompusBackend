<?php

namespace App\Controller\Api;
use App\Entity\User;
use App\Entity\Administrator;
use App\Entity\Teacher;
use App\Entity\Student;
use App\Entity\ParentUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
#[Route('/api/ParentUsers')]
class ParentUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'parent')]
    public function index(): JsonResponse
    {
        $ParentUsers = $this->entityManager->getRepository(ParentUser::class)->findAll();
        return $this->json($ParentUsers);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'parent')]

    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $ParentUser = new ParentUser();
        $ParentUser->setProfession($data['profession']);
        $ParentUser->setTelephone($data['telephone']);
        
        $this->entityManager->persist($ParentUser);
        $this->entityManager->flush();
        
        return $this->json($ParentUser, 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'parent')]

    public function show(ParentUser $ParentUser): JsonResponse
    {
        return $this->json($ParentUser);
    }

    #[Route('/{id}/enfants', methods: ['GET'])]
    #[OA\Tag(name: 'parent')]

    public function getEnfants(ParentUser $ParentUser): JsonResponse
    {
        return $this->json($ParentUser->getEnfants());
    }
}
