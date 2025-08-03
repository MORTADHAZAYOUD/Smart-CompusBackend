<?php

namespace App\Controller\Api;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/classes')]
class ClasseController extends AbstractController
{
    #[Route('', name: 'get_all_classes', methods: ['GET'])]
    #[OA\Tag(name: 'Classe')]

    public function getAll(ClasseRepository $classeRepository): JsonResponse
    {
        $classes = $classeRepository->findAll();
        $data = [];

        foreach ($classes as $classe) {
            $data[] = [
                'id' => $classe->getId(),
                'nom' => $classe->getNom(),
                'nb_students' => count($classe->getStudent()),
            ];
        }

        return new JsonResponse($data, 200);
    }

    #[Route('/{id}', name: 'get_class', methods: ['GET'])]
    #[OA\Tag(name: 'Classe')]

    public function getOne(Classe $classe): JsonResponse
    {
        $students = [];
        foreach ($classe->getStudent() as $student) {
            $students[] = [
                'id' => $student->getId(),
                'nom' => $student->getLastname(),
                'prenom' => $student->getFirstname(),
            ];
        }

        return new JsonResponse([
            'id' => $classe->getId(),
            'nom' => $classe->getNom(),
            'students' => $students
        ], 200);
    }

    #[Route('', name: 'create_class', methods: ['POST'])]
    #[OA\Tag(name: 'Classe')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'])) {
            return new JsonResponse(['error' => 'Le champ "nom" est requis.'], 400);
        }

        $classe = new Classe();
        $classe->setNom($data['nom']);

        $em->persist($classe);
        $em->flush();

        return new JsonResponse([
            'message' => 'Classe créée',
            'id' => $classe->getId()
        ], 201);
    }

    #[Route('/{id}', name: 'update_class', methods: ['PUT'])]
    #[OA\Tag(name: 'Classe')]
    public function update(Request $request, Classe $classe, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $classe->setNom($data['nom']);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Classe mise à jour'], 200);
    }

    #[Route('/{id}', name: 'delete_class', methods: ['DELETE'])]
    #[OA\Tag(name: 'Classe')]
    public function delete(Classe $classe, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($classe);
        $em->flush();

        return new JsonResponse(['message' => 'Classe supprimée'], 200);
    }
}
