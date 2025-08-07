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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/students')]
#[IsGranted('ROLE_USER')]
class StudentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private StudentRepository $studentRepository,
        private ClasseRepository $classeRepository,
        private ParentUserRepository $parentRepository,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_students_index', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[OA\Get(
        path: '/api/students',
        summary: 'Get all students',
        parameters: [
            new OA\Parameter(
                name: 'classe',
                in: 'query',
                description: 'Filter by class ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search by name or student number',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Students list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'students', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'pagination', type: 'object')
                    ]
                )
            )
        ],
        security: [['bearerAuth' => []]]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $classe = $request->query->get('classe');
            $search = $request->query->get('search');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;
            
            $queryBuilder = $this->studentRepository->createQueryBuilder('s')
                ->leftJoin('s.classe', 'c')
                ->leftJoin('s.parent', 'p');
            
            // Apply access control based on user role
            if (in_array('ROLE_PARENT', $user->getRoles())) {
                // Parents can only see their own children
                $queryBuilder->andWhere('s.parent = :parent')
                           ->setParameter('parent', $user);
            } elseif (in_array('ROLE_STUDENT', $user->getRoles())) {
                // Students can only see themselves
                $queryBuilder->andWhere('s.id = :studentId')
                           ->setParameter('studentId', $user->getId());
            }
            
            // Filter by class
            if ($classe) {
                $queryBuilder->andWhere('c.id = :classe')
                           ->setParameter('classe', $classe);
            }
            
            // Search functionality
            if ($search) {
                $queryBuilder->andWhere('s.firstname LIKE :search OR s.lastname LIKE :search OR s.numStudent LIKE :search')
                           ->setParameter('search', '%' . $search . '%');
            }
            
            // Get total count
            $totalQuery = clone $queryBuilder;
            $total = $totalQuery->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();
            
            // Get paginated results
            $students = $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            return new JsonResponse([
                'students' => json_decode($this->serializer->serialize($students, 'json', ['groups' => ['student:read']]), true),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch students: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_students_show', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[OA\Get(
        path: '/api/students/{id}',
        summary: 'Get student by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Student details'),
            new OA\Response(response: 404, description: 'Student not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $student = $this->studentRepository->find($id);
            if (!$student) {
                return new JsonResponse(['error' => 'Student not found'], 404);
            }

            // Check access permissions
            $user = $this->getUser();
            if (!$this->canAccessStudent($user, $student)) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            return $this->json($student, 200, [], ['groups' => ['student:read', 'student:details']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch student: ' . $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'api_students_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Tag(name: 'student')]
    #[OA\Post(
        path: '/api/students',
        summary: 'Create new student',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'firstname', 'lastname', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'firstname', type: 'string'),
                    new OA\Property(property: 'lastname', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'numStudent', type: 'string'),
                    new OA\Property(property: 'dateNaissance', type: 'string', format: 'date'),
                    new OA\Property(property: 'classeId', type: 'integer'),
                    new OA\Property(property: 'parentId', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Student created successfully'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Student already exists')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            $requiredFields = ['email', 'firstname', 'lastname', 'password'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse(['error' => "Field '{$field}' is required"], 400);
                }
            }

            // Check if student already exists
            $existingStudent = $this->studentRepository->findOneBy(['email' => $data['email']]);
            if ($existingStudent) {
                return new JsonResponse(['error' => 'Student with this email already exists'], 409);
            }

            $student = new Student();
            $student->setEmail($data['email'])
                   ->setFirstname($data['firstname'])
                   ->setLastname($data['lastname'])
                   ->setPassword($passwordHasher->hashPassword($student, $data['password']))
                   ->setRoles(['ROLE_STUDENT']);

            // Set optional fields
            if (isset($data['numStudent'])) {
                $student->setNumStudent($data['numStudent']);
            }
            
            if (isset($data['dateNaissance'])) {
                $student->setDateNaissance(new \DateTime($data['dateNaissance']));
            }

            // Set class if provided
            if (isset($data['classeId'])) {
                $classe = $this->classeRepository->find($data['classeId']);
                if ($classe) {
                    $student->setClasse($classe);
                }
            }

            // Set parent if provided
            if (isset($data['parentId'])) {
                $parent = $this->parentRepository->find($data['parentId']);
                if ($parent) {
                    $student->setParent($parent);
                }
            }

            // Validate student
            $errors = $this->validator->validate($student);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $this->em->persist($student);
            $this->em->flush();

            return $this->json($student, 201, [], ['groups' => ['student:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create student: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_students_update', methods: ['PUT'])]
    #[OA\Tag(name: 'student')]
    #[OA\Put(
        path: '/api/students/{id}',
        summary: 'Update student',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Student updated successfully'),
            new OA\Response(response: 404, description: 'Student not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $student = $this->studentRepository->find($id);
            if (!$student) {
                return new JsonResponse(['error' => 'Student not found'], 404);
            }

            // Check permissions
            $user = $this->getUser();
            if (!$this->canModifyStudent($user, $student)) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), true);
            
            // Update basic fields
            if (isset($data['firstname'])) $student->setFirstname($data['firstname']);
            if (isset($data['lastname'])) $student->setLastname($data['lastname']);
            if (isset($data['email'])) {
                // Check if email is already taken
                $existingStudent = $this->studentRepository->findOneBy(['email' => $data['email']]);
                if ($existingStudent && $existingStudent->getId() !== $id) {
                    return new JsonResponse(['error' => 'Email already taken'], 409);
                }
                $student->setEmail($data['email']);
            }

            // Update student-specific fields
            if (isset($data['numStudent'])) $student->setNumStudent($data['numStudent']);
            if (isset($data['dateNaissance'])) {
                $student->setDateNaissance(new \DateTime($data['dateNaissance']));
            }

            // Update class if provided (admin only)
            if (isset($data['classeId']) && in_array('ROLE_ADMIN', $user->getRoles())) {
                $classe = $this->classeRepository->find($data['classeId']);
                $student->setClasse($classe);
            }

            // Update parent if provided (admin only)
            if (isset($data['parentId']) && in_array('ROLE_ADMIN', $user->getRoles())) {
                $parent = $this->parentRepository->find($data['parentId']);
                $student->setParent($parent);
            }

            // Validate student
            $errors = $this->validator->validate($student);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $this->em->flush();

            return $this->json($student, 200, [], ['groups' => ['student:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update student: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_students_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Tag(name: 'student')]
    #[OA\Delete(
        path: '/api/students/{id}',
        summary: 'Delete student',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Student deleted successfully'),
            new OA\Response(response: 404, description: 'Student not found')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function delete(int $id): JsonResponse
    {
        try {
            $student = $this->studentRepository->find($id);
            if (!$student) {
                return new JsonResponse(['error' => 'Student not found'], 404);
            }

            $this->em->remove($student);
            $this->em->flush();

            return new JsonResponse(['message' => 'Student deleted successfully']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete student: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/grades', name: 'api_students_grades', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[OA\Get(
        path: '/api/students/{id}/grades',
        summary: 'Get student grades',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Student grades'),
            new OA\Response(response: 404, description: 'Student not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function getGrades(int $id): JsonResponse
    {
        try {
            $student = $this->studentRepository->find($id);
            if (!$student) {
                return new JsonResponse(['error' => 'Student not found'], 404);
            }

            // Check access permissions
            $user = $this->getUser();
            if (!$this->canAccessStudent($user, $student)) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            // Get grades for this student
            $grades = $this->em->getRepository('App\Entity\Note')->findBy(['student' => $student]);

            return $this->json($grades, 200, [], ['groups' => ['grade:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch grades: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/attendance', name: 'api_students_attendance', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[OA\Get(
        path: '/api/students/{id}/attendance',
        summary: 'Get student attendance',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Student attendance'),
            new OA\Response(response: 404, description: 'Student not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function getAttendance(int $id): JsonResponse
    {
        try {
            $student = $this->studentRepository->find($id);
            if (!$student) {
                return new JsonResponse(['error' => 'Student not found'], 404);
            }

            // Check access permissions
            $user = $this->getUser();
            if (!$this->canAccessStudent($user, $student)) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            // Get attendance records for this student
            $attendance = $this->em->getRepository('App\Entity\Presence')->findBy(['student' => $student]);

            return $this->json($attendance, 200, [], ['groups' => ['attendance:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch attendance: ' . $e->getMessage()], 500);
        }
    }

    private function canAccessStudent($user, Student $student): bool
    {
        // Admin can access all students
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Teachers can access students in their classes
        if (in_array('ROLE_TEACHER', $user->getRoles())) {
            // This would require checking if teacher teaches this student's class
            // Implementation depends on your teacher-class relationship
            return true; // Simplified for now
        }

        // Parents can access their children
        if (in_array('ROLE_PARENT', $user->getRoles())) {
            return $student->getParent() && $student->getParent()->getId() === $user->getId();
        }

        // Students can access their own profile
        if (in_array('ROLE_STUDENT', $user->getRoles())) {
            return $student->getId() === $user->getId();
        }

        return false;
    }

    private function canModifyStudent($user, Student $student): bool
    {
        // Admin can modify all students
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Students can modify their own profile (limited fields)
        if (in_array('ROLE_STUDENT', $user->getRoles())) {
            return $student->getId() === $user->getId();
        }

        return false;
    }
}