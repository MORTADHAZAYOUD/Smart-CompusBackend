<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController  extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Tag(name: 'authentication')]
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
}