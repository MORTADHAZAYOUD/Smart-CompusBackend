<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiDocController extends AbstractController
{
    #[Route('/info', name: 'api_info', methods: ['GET'])]
    #[OA\Tag(name: 'api-info')]
    #[OA\Response(
        response: 200,
        description: 'API information and available endpoints'
    )]
    public function apiInfo(): JsonResponse
    {
        return new JsonResponse([
            'name' => 'Smart Campus API',
            'version' => '1.0.0',
            'description' => 'Comprehensive school management system API',
            'endpoints' => [
                'authentication' => [
                    'login' => 'POST /api/login',
                    'register' => 'POST /api/register'
                ],
                'users' => [
                    'list' => 'GET /api/users',
                    'show' => 'GET /api/users/{id}',
                    'update' => 'PUT /api/users/{id}',
                    'delete' => 'DELETE /api/users/{id}'
                ],
                'students' => [
                    'list' => 'GET /api/students',
                    'show' => 'GET /api/students/{id}',
                    'create' => 'POST /api/students',
                    'update' => 'PUT /api/students/{id}',
                    'delete' => 'DELETE /api/students/{id}'
                ],
                'teachers' => [
                    'list' => 'GET /api/teachers',
                    'show' => 'GET /api/teachers/{id}',
                    'create' => 'POST /api/teachers',
                    'update' => 'PUT /api/teachers/{id}',
                    'delete' => 'DELETE /api/teachers/{id}'
                ],
                'classes' => [
                    'list' => 'GET /api/classes',
                    'show' => 'GET /api/classes/{id}',
                    'create' => 'POST /api/classes',
                    'update' => 'PUT /api/classes/{id}',
                    'delete' => 'DELETE /api/classes/{id}'
                ],
                'messages' => [
                    'conversations' => 'GET /api/messages/conversations',
                    'conversation' => 'GET /api/messages/conversation/{id}',
                    'send' => 'POST /api/messages/send',
                    'create_conversation' => 'POST /api/messages/conversation'
                ],
                'notifications' => [
                    'list' => 'GET /api/notifications',
                    'mark_read' => 'PUT /api/notifications/{id}/read',
                    'mark_all_read' => 'PUT /api/notifications/read-all'
                ]
            ],
            'authentication' => [
                'type' => 'Bearer Token (JWT)',
                'header' => 'Authorization: Bearer {token}',
                'login_endpoint' => '/api/login',
                'token_ttl' => '3600 seconds'
            ],
            'documentation' => '/api/doc'
        ]);
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
    #[OA\Tag(name: 'api-info')]
    #[OA\Response(
        response: 200,
        description: 'API health check'
    )]
    public function healthCheck(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'timestamp' => new \DateTime(),
            'version' => '1.0.0'
        ]);
    }
}