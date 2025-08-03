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
// 9. EvenementController - Gestion des événements
#[Route('/api/evenements')]
class EvenementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'evenement')]
    public function index(): JsonResponse
    {
        $evenements = $this->entityManager->getRepository(Evenement::class)->findAll();
        return $this->json($evenements);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'evenement')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $evenement = new Evenement();
        $evenement->setTitre($data['titre']);
        $evenement->setDescription($data['description']);
        $evenement->setDateDebut(new \DateTime($data['dateDebut']));
        $evenement->setDateFin(new \DateTime($data['dateFin']));
        $evenement->setType($data['type']);
        
        $this->entityManager->persist($evenement);
        $this->entityManager->flush();
        
        return $this->json($evenement, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'evenement')]
    public function update(Request $request, Evenement $evenement): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $evenement->setTitre($data['titre'] ?? $evenement->getTitre());
        $evenement->setDescription($data['description'] ?? $evenement->getDescription());
        $evenement->setType($data['type'] ?? $evenement->getType());
        
        $this->entityManager->flush();
        
        return $this->json($evenement);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'evenement')]
    public function delete(Evenement $evenement): JsonResponse
    {
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Événement supprimé avec succès']);
    }
}
