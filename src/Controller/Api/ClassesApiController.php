<?php

namespace App\Controller\Api;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/classes')]
class ClassesApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClasseRepository $classeRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 200, description: 'List of classes')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $search = $request->query->get('search');

        $queryBuilder = $this->classeRepository->createQueryBuilder('c')
            ->leftJoin('c.students', 's')
            ->addSelect('s')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($search) {
            $queryBuilder->andWhere('c.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $classes = $queryBuilder->getQuery()->getResult();
        $total = $this->classeRepository->count([]);

        return $this->json([
            'data' => $classes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], 200, [], ['groups' => ['classe:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 200, description: 'Class details')]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $classe = $this->classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        return $this->json($classe, 200, [], ['groups' => ['classe:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 201, description: 'Class created')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (empty($data['nom'])) {
            return $this->json(['error' => 'Field "nom" is required'], 400);
        }

        // Check if class name already exists
        $existingClasse = $this->classeRepository->findOneBy(['nom' => $data['nom']]);
        if ($existingClasse) {
            return $this->json(['error' => 'Class name already exists'], 409);
        }

        $classe = new Classe();
        $classe->setNom($data['nom']);

        $errors = $this->validator->validate($classe);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->persist($classe);
        $this->em->flush();

        return $this->json($classe, 201, [], ['groups' => ['classe:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 200, description: 'Class updated')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $classe = $this->classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (isset($data['nom'])) {
            // Check if class name already exists for another class
            $existingClasse = $this->classeRepository->findOneBy(['nom' => $data['nom']]);
            if ($existingClasse && $existingClasse->getId() !== $classe->getId()) {
                return $this->json(['error' => 'Class name already exists'], 409);
            }
            $classe->setNom($data['nom']);
        }

        $errors = $this->validator->validate($classe);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->flush();

        return $this->json($classe, 200, [], ['groups' => ['classe:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 204, description: 'Class deleted')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $classe = $this->classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        // Check if class has students
        if (count($classe->getStudents()) > 0) {
            return $this->json(['error' => 'Cannot delete class with students'], 409);
        }

        $this->em->remove($classe);
        $this->em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/students', methods: ['GET'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 200, description: 'Students in class')]
    #[IsGranted('ROLE_TEACHER')]
    public function getStudents(int $id): JsonResponse
    {
        $classe = $this->classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $students = $classe->getStudents();

        return $this->json($students, 200, [], ['groups' => ['student:read']]);
    }

    #[Route('/{id}/stats', methods: ['GET'])]
    #[OA\Tag(name: 'classes')]
    #[OA\Response(response: 200, description: 'Class statistics')]
    #[IsGranted('ROLE_TEACHER')]
    public function getStats(int $id): JsonResponse
    {
        $classe = $this->classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $students = $classe->getStudents();
        $totalStudents = count($students);
        
        // Calculate age statistics
        $ages = [];
        foreach ($students as $student) {
            if ($student->getDateNaissance()) {
                $age = $student->getDateNaissance()->diff(new \DateTime())->y;
                $ages[] = $age;
            }
        }

        $avgAge = count($ages) > 0 ? array_sum($ages) / count($ages) : 0;

        return $this->json([
            'classId' => $classe->getId(),
            'className' => $classe->getNom(),
            'totalStudents' => $totalStudents,
            'averageAge' => round($avgAge, 2),
            'minAge' => count($ages) > 0 ? min($ages) : 0,
            'maxAge' => count($ages) > 0 ? max($ages) : 0,
        ]);
    }
}