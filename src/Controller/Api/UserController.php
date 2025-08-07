<?php
// src/Controller/UserController.php
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'user')]
    #[OA\Get(
        path: '/api/users',
        summary: 'Get all users',
        parameters: [
            new OA\Parameter(
                name: 'role',
                in: 'query',
                description: 'Filter by role',
                schema: new OA\Schema(type: 'string', enum: ['admin', 'teacher', 'student', 'parent'])
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
                description: 'Users list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'object')
                )
            )
        ],
        security: [['bearerAuth' => []]]
    )]
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $role = $request->query->get('role');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            $queryBuilder = $this->userRepository->createQueryBuilder('u');
            
            if ($role) {
                $queryBuilder->andWhere('u.roles LIKE :role')
                           ->setParameter('role', '%' . strtoupper('ROLE_' . $role) . '%');
            }

            $totalQuery = clone $queryBuilder;
            $total = $totalQuery->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

            $users = $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            return new JsonResponse([
                'users' => json_decode($this->serializer->serialize($users, 'json', ['groups' => ['user:read']]), true),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch users: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'user')]
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Get user by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'User details'),
            new OA\Response(response: 404, description: 'User not found')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function showUser(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }
            
            // Check if user can access this profile (own profile or admin)
            $currentUser = $this->getUser();
            if ($currentUser->getId() !== $id && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            return $this->json($user, 200, [], ['groups' => ['user:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Tag(name: 'user')]
    #[OA\Post(
        path: '/api/users',
        summary: 'Create new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'firstname', 'lastname', 'role', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'firstname', type: 'string'),
                    new OA\Property(property: 'lastname', type: 'string'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'teacher', 'student', 'parent']),
                    new OA\Property(property: 'password', type: 'string', minLength: 6)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'User already exists')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            $requiredFields = ['email', 'firstname', 'lastname', 'role', 'password'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse(['error' => "Field '{$field}' is required"], 400);
                }
            }

            // Check if user already exists
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'User with this email already exists'], 409);
            }

            // Factory pattern pour créer le bon type d'utilisateur
            $user = match($data['role']) {
                'admin' => new Administrator(),
                'teacher' => new Teacher(),
                'student' => new Student(),
                'parent' => new ParentUser(),
                default => throw new \InvalidArgumentException('Invalid role')
            };

            // Mapping des propriétés communes
            $user->setEmail($data['email'])
                 ->setFirstname($data['firstname'])
                 ->setLastname($data['lastname'])
                 ->setPassword($passwordHasher->hashPassword($user, $data['password']));

            // Set roles based on user type
            $this->setUserRoles($user, $data['role']);

            // Propriétés spécifiques selon le type
            $this->setRoleSpecificProperties($user, $data);

            // Validate user
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $this->em->persist($user);
            $this->em->flush();

            return $this->json($user, 201, [], ['groups' => ['user:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'user')]
    #[OA\Put(
        path: '/api/users/{id}',
        summary: 'Update user',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'User updated successfully'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Check if user can update this profile (own profile or admin)
            $currentUser = $this->getUser();
            if ($currentUser->getId() !== $id && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), true);
            
            // Update basic properties
            if (isset($data['email'])) {
                // Check if email is already taken by another user
                $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
                if ($existingUser && $existingUser->getId() !== $id) {
                    return new JsonResponse(['error' => 'Email already taken'], 409);
                }
                $user->setEmail($data['email']);
            }
            
            if (isset($data['firstname'])) $user->setFirstname($data['firstname']);
            if (isset($data['lastname'])) $user->setLastname($data['lastname']);

            // Update role-specific properties
            $this->setRoleSpecificProperties($user, $data);

            // Validate user
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $this->em->flush();

            return $this->json($user, 200, [], ['groups' => ['user:read']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/password', methods: ['PUT'])]
    #[OA\Tag(name: 'user')]
    #[OA\Put(
        path: '/api/users/{id}/password',
        summary: 'Update user password',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['currentPassword', 'newPassword'],
                properties: [
                    new OA\Property(property: 'currentPassword', type: 'string'),
                    new OA\Property(property: 'newPassword', type: 'string', minLength: 6)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password updated successfully'),
            new OA\Response(response: 400, description: 'Invalid current password'),
            new OA\Response(response: 404, description: 'User not found')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function updatePassword(
        int $id, 
        Request $request, 
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Check if user can update this password (own profile or admin)
            $currentUser = $this->getUser();
            if ($currentUser->getId() !== $id && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['newPassword'])) {
                return new JsonResponse(['error' => 'New password is required'], 400);
            }

            // For non-admin users, verify current password
            if (!in_array('ROLE_ADMIN', $currentUser->getRoles()) || $currentUser->getId() === $id) {
                if (!isset($data['currentPassword'])) {
                    return new JsonResponse(['error' => 'Current password is required'], 400);
                }
                
                if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                    return new JsonResponse(['error' => 'Invalid current password'], 400);
                }
            }

            $user->setPassword($passwordHasher->hashPassword($user, $data['newPassword']));
            $this->em->flush();

            return new JsonResponse(['message' => 'Password updated successfully']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Tag(name: 'user')]
    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Delete user',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'User deleted successfully'),
            new OA\Response(response: 404, description: 'User not found')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function deleteUser(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Prevent self-deletion
            $currentUser = $this->getUser();
            if ($currentUser->getId() === $id) {
                return new JsonResponse(['error' => 'Cannot delete your own account'], 400);
            }

            $this->em->remove($user);
            $this->em->flush();

            return new JsonResponse(['message' => 'User deleted successfully']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/profile', methods: ['GET'])]
    #[OA\Tag(name: 'user')]
    #[OA\Get(
        path: '/api/users/profile',
        summary: 'Get current user profile',
        responses: [
            new OA\Response(response: 200, description: 'User profile'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['bearerAuth' => []]]
    )]
    public function getProfile(): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 401);
            }

            return $this->json($user, 200, [], ['groups' => ['user:read', 'user:profile']]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch profile: ' . $e->getMessage()], 500);
        }
    }

    private function setUserRoles(User $user, string $role): void
    {
        $roleMap = [
            'admin' => ['ROLE_ADMIN'],
            'teacher' => ['ROLE_TEACHER'],
            'student' => ['ROLE_STUDENT'],
            'parent' => ['ROLE_PARENT']
        ];

        $user->setRoles($roleMap[$role] ?? ['ROLE_USER']);
    }

    private function setRoleSpecificProperties(User $user, array $data): void
    {
        if ($user instanceof Teacher && isset($data['specialite'])) {
            $user->setSpecialite($data['specialite']);
        }
        
        if ($user instanceof ParentUser) {
            if (isset($data['profession'])) $user->setProfession($data['profession']);
            if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
        }
        
        if ($user instanceof Student) {
            if (isset($data['numStudent'])) $user->setNumStudent($data['numStudent']);
            if (isset($data['dateNaissance'])) {
                $user->setDateNaissance(new \DateTime($data['dateNaissance']));
            }
        }

        if ($user instanceof Administrator && isset($data['privileges'])) {
            $user->setPrivileges($data['privileges']);
        }
    }
}