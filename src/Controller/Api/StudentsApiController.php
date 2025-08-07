<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Classe;
use App\Entity\ParentUser;
use App\Repository\StudentRepository;
use App\Repository\ClasseRepository;
use App\Repository\ParentUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/students')]
class StudentsApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private StudentRepository $studentRepository,
        private ClasseRepository $classeRepository,
        private ParentUserRepository $parentRepository,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 200, description: 'List of students')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $search = $request->query->get('search');
        $classeId = $request->query->get('classe');

        $queryBuilder = $this->studentRepository->createQueryBuilder('s')
            ->leftJoin('s.classe', 'c')
            ->leftJoin('s.parent', 'p')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($search) {
            $queryBuilder->andWhere('s.firstname LIKE :search OR s.lastname LIKE :search OR s.numStudent LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($classeId) {
            $queryBuilder->andWhere('c.id = :classeId')
                ->setParameter('classeId', $classeId);
        }

        $students = $queryBuilder->getQuery()->getResult();
        $total = $this->studentRepository->count([]);

        return $this->json([
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], 200, [], ['groups' => ['student:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 200, description: 'Student details')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(int $id): JsonResponse
    {
        $student = $this->studentRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        return $this->json($student, 200, [], ['groups' => ['student:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 201, description: 'Student created')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        // Validate required fields
        $requiredFields = ['email', 'firstname', 'lastname', 'numStudent', 'dateNaissance'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Field '$field' is required"], 400);
            }
        }

        // Check if email already exists
        $existingUser = $this->em->getRepository(Student::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        $student = new Student();
        $student->setEmail($data['email']);
        $student->setFirstname($data['firstname']);
        $student->setLastname($data['lastname']);
        $student->setNumStudent($data['numStudent']);
        $student->setDateNaissance(new \DateTime($data['dateNaissance']));
        $student->setRoles(['ROLE_STUDENT']);

        // Set default password if not provided
        $password = $data['password'] ?? 'student123';
        $hashedPassword = $this->passwordHasher->hashPassword($student, $password);
        $student->setPassword($hashedPassword);

        // Set class if provided
        if (!empty($data['classeId'])) {
            $classe = $this->classeRepository->find($data['classeId']);
            if ($classe) {
                $student->setClasse($classe);
            }
        }

        // Set parent if provided
        if (!empty($data['parentId'])) {
            $parent = $this->parentRepository->find($data['parentId']);
            if ($parent) {
                $student->setParent($parent);
            }
        }

        $errors = $this->validator->validate($student);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->persist($student);
        $this->em->flush();

        return $this->json($student, 201, [], ['groups' => ['student:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 200, description: 'Student updated')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $student = $this->studentRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        // Update fields if provided
        if (isset($data['email'])) {
            // Check if email already exists for another user
            $existingUser = $this->em->getRepository(Student::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $student->getId()) {
                return $this->json(['error' => 'Email already exists'], 409);
            }
            $student->setEmail($data['email']);
        }

        if (isset($data['firstname'])) {
            $student->setFirstname($data['firstname']);
        }

        if (isset($data['lastname'])) {
            $student->setLastname($data['lastname']);
        }

        if (isset($data['numStudent'])) {
            $student->setNumStudent($data['numStudent']);
        }

        if (isset($data['dateNaissance'])) {
            $student->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        if (isset($data['classeId'])) {
            $classe = $data['classeId'] ? $this->classeRepository->find($data['classeId']) : null;
            $student->setClasse($classe);
        }

        if (isset($data['parentId'])) {
            $parent = $data['parentId'] ? $this->parentRepository->find($data['parentId']) : null;
            $student->setParent($parent);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($student, $data['password']);
            $student->setPassword($hashedPassword);
        }

        $errors = $this->validator->validate($student);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->flush();

        return $this->json($student, 200, [], ['groups' => ['student:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 204, description: 'Student deleted')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $student = $this->studentRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        $this->em->remove($student);
        $this->em->flush();

        return $this->json(null, 204);
    }

    #[Route('/by-class/{classeId}', methods: ['GET'])]
    #[OA\Tag(name: 'students')]
    #[OA\Response(response: 200, description: 'Students by class')]
    #[IsGranted('ROLE_TEACHER')]
    public function getByClass(int $classeId): JsonResponse
    {
        $classe = $this->classeRepository->find($classeId);

        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $students = $this->studentRepository->findBy(['classe' => $classe]);

        return $this->json($students, 200, [], ['groups' => ['student:read']]);
    }
}