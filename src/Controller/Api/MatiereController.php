<?php

namespace App\Controller\Api;

use App\Entity\Matiere;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/matieres')]
class MatiereController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MatiereRepository $matiereRepository
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'matiere')]
    public function index(): JsonResponse
    {
        $matieres = $this->matiereRepository->findAll();
        return $this->json($matieres, 200, [], ['groups' => ['matiere:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'matiere')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['nom'])) {
            return $this->json(['error' => 'Le nom de la matière est requis'], 400);
        }
        
        $matiere = new Matiere();
        $matiere->setNom($data['nom']);
        
        $this->em->persist($matiere);
        $this->em->flush();
        
        return $this->json($matiere, 201, [], ['groups' => ['matiere:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'matiere')]
    public function show(Matiere $matiere): JsonResponse
    {
        return $this->json($matiere, 200, [], ['groups' => ['matiere:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'matiere')]
    public function update(Request $request, Matiere $matiere): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nom'])) {
            $matiere->setNom($data['nom']);
        }
        
        $this->em->flush();
        
        return $this->json($matiere, 200, [], ['groups' => ['matiere:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'matiere')]
    public function delete(Matiere $matiere): JsonResponse
    {
        $this->em->remove($matiere);
        $this->em->flush();
        
        return $this->json(['message' => 'Matière supprimée avec succès'], 200);
    }
}
