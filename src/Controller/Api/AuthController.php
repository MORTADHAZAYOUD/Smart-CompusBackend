<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Tag(name: 'authentication')]
    #[OA\Post(
        path: '/api/login',
        summary: 'User login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 400, description: 'Validation error')
        ]
    )]
    public function apiLogin(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Check for required fields
        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        try {
            // Find user by email
            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            
            if (!$user) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }

            // Check password
            if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }

            // Generate JWT token
            $token = $jwtManager->create($user);

            return new JsonResponse([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'roles' => $user->getRoles()
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Tag(name: 'authentication')]
    #[OA\Post(
        path: '/api/register',
        summary: 'User registration',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstname', 'lastname', 'role'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'firstname', type: 'string'),
                    new OA\Property(property: 'lastname', type: 'string'),
                    new OA\Property(property: 'role', type: 'string', enum: ['student', 'teacher', 'parent', 'admin'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'User already exists')
        ]
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Check for required fields
        $requiredFields = ['email', 'password', 'firstname', 'lastname', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new JsonResponse(['error' => "Field '{$field}' is required"], 400);
            }
        }

        try {
            // Check if user already exists
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'User with this email already exists'], 409);
            }

            // Create user based on role
            $user = $this->createUserByRole($data['role']);
            if (!$user) {
                return new JsonResponse(['error' => 'Invalid role specified'], 400);
            }

            $user->setEmail($data['email']);
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

            // Set role-specific properties
            $this->setRoleSpecificProperties($user, $data);

            // Validate user
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $em->persist($user);
            $em->flush();

            return new JsonResponse([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'roles' => $user->getRoles()
                ]
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Tag(name: 'authentication')]
    #[OA\Post(
        path: '/api/refresh-token',
        summary: 'Refresh JWT token',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function refreshToken(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 401);
            }

            $token = $jwtManager->create($user);

            return new JsonResponse([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'roles' => $user->getRoles()
                ]
            ], 200);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token refresh failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Tag(name: 'authentication')]
    #[OA\Post(
        path: '/api/logout',
        summary: 'User logout',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        // With JWT, logout is mainly handled client-side by removing the token
        // Here we just confirm the logout action
        return new JsonResponse(['message' => 'Logout successful'], 200);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Tag(name: 'authentication')]
    #[OA\Get(
        path: '/api/me',
        summary: 'Get current user info',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 401);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles()
            ]
        ], 200);
    }

    private function createUserByRole(string $role): ?User
    {
        return match ($role) {
            'student' => new Student(),
            'teacher' => new Teacher(),
            'parent' => new ParentUser(),
            'admin' => new Administrator(),
            default => null,
        };
    }

    private function setRoleSpecificProperties(User $user, array $data): void
    {
        // Set role-specific properties based on user type
        if ($user instanceof Student) {
            $user->setRoles(['ROLE_STUDENT']);
            // Add student-specific properties if provided
            if (isset($data['numStudent'])) {
                $user->setNumStudent($data['numStudent']);
            }
        } elseif ($user instanceof Teacher) {
            $user->setRoles(['ROLE_TEACHER']);
            // Add teacher-specific properties if provided
        } elseif ($user instanceof ParentUser) {
            $user->setRoles(['ROLE_PARENT']);
            // Add parent-specific properties if provided
        } elseif ($user instanceof Administrator) {
            $user->setRoles(['ROLE_ADMIN']);
            // Add admin-specific properties if provided
        }
    }
}