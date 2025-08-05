<?php

namespace App\Controller\Api;

use App\Service\AlertService;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/alerts')]
class AlertController extends AbstractController
{
    public function __construct(
        private AlertService $alertService,
        private ClasseRepository $classeRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('/send/students', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'content' => new OA\Property(property: 'content', type: 'string'),
                'studentIds' => new OA\Property(property: 'studentIds', type: 'array', items: new OA\Items(type: 'integer')),
                'priority' => new OA\Property(property: 'priority', type: 'string', enum: ['normale', 'haute', 'urgente'])
            ]
        )
    )]
    public function sendAlertToStudents(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            return $this->json(['error' => 'Title and content are required'], 400);
        }

        $notifications = $this->alertService->sendAlertToStudents(
            $data['title'],
            $data['content'],
            $data['studentIds'] ?? [],
            $data['priority'] ?? 'normale'
        );

        return $this->json([
            'message' => 'Alert sent to students successfully',
            'sent_count' => count($notifications)
        ], 200);
    }

    #[Route('/send/parents', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'content' => new OA\Property(property: 'content', type: 'string'),
                'parentIds' => new OA\Property(property: 'parentIds', type: 'array', items: new OA\Items(type: 'integer')),
                'priority' => new OA\Property(property: 'priority', type: 'string', enum: ['normale', 'haute', 'urgente'])
            ]
        )
    )]
    public function sendAlertToParents(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            return $this->json(['error' => 'Title and content are required'], 400);
        }

        $notifications = $this->alertService->sendAlertToParents(
            $data['title'],
            $data['content'],
            $data['parentIds'] ?? [],
            $data['priority'] ?? 'normale'
        );

        return $this->json([
            'message' => 'Alert sent to parents successfully',
            'sent_count' => count($notifications)
        ], 200);
    }

    #[Route('/send/class/{classeId}', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'content' => new OA\Property(property: 'content', type: 'string'),
                'includeParents' => new OA\Property(property: 'includeParents', type: 'boolean'),
                'priority' => new OA\Property(property: 'priority', type: 'string', enum: ['normale', 'haute', 'urgente'])
            ]
        )
    )]
    public function sendAlertToClass(int $classeId, Request $request): JsonResponse
    {
        $classe = $this->classeRepository->find($classeId);
        
        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            return $this->json(['error' => 'Title and content are required'], 400);
        }

        $notifications = $this->alertService->sendAlertToClass(
            $data['title'],
            $data['content'],
            $classe,
            $data['includeParents'] ?? true,
            $data['priority'] ?? 'normale'
        );

        return $this->json([
            'message' => 'Alert sent to class successfully',
            'sent_count' => count($notifications),
            'class_name' => $classe->getNom()
        ], 200);
    }

    #[Route('/send/exam', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'examTitle' => new OA\Property(property: 'examTitle', type: 'string'),
                'examDate' => new OA\Property(property: 'examDate', type: 'string', format: 'date-time'),
                'classeId' => new OA\Property(property: 'classeId', type: 'integer'),
                'subject' => new OA\Property(property: 'subject', type: 'string'),
                'location' => new OA\Property(property: 'location', type: 'string')
            ]
        )
    )]
    public function sendExamAlert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['examTitle']) || !isset($data['examDate']) || !isset($data['classeId'])) {
            return $this->json(['error' => 'Exam title, date, and class ID are required'], 400);
        }

        $classe = $this->classeRepository->find($data['classeId']);
        
        if (!$classe) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        $examDate = new \DateTime($data['examDate']);
        
        $notifications = $this->alertService->sendExamAlert(
            $data['examTitle'],
            $examDate,
            $classe,
            $data['subject'] ?? null,
            $data['location'] ?? null
        );

        return $this->json([
            'message' => 'Exam alert sent successfully',
            'sent_count' => count($notifications),
            'exam_title' => $data['examTitle'],
            'class_name' => $classe->getNom()
        ], 200);
    }

    #[Route('/send/vacation', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'vacationName' => new OA\Property(property: 'vacationName', type: 'string'),
                'startDate' => new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                'endDate' => new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                'classIds' => new OA\Property(property: 'classIds', type: 'array', items: new OA\Items(type: 'integer'))
            ]
        )
    )]
    public function sendVacationAlert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['vacationName']) || !isset($data['startDate']) || !isset($data['endDate'])) {
            return $this->json(['error' => 'Vacation name, start date, and end date are required'], 400);
        }

        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);
        
        $classes = [];
        if (isset($data['classIds']) && !empty($data['classIds'])) {
            $classes = $this->classeRepository->findBy(['id' => $data['classIds']]);
        }

        $notifications = $this->alertService->sendVacationAlert(
            $data['vacationName'],
            $startDate,
            $endDate,
            $classes
        );

        return $this->json([
            'message' => 'Vacation alert sent successfully',
            'sent_count' => count($notifications),
            'vacation_name' => $data['vacationName']
        ], 200);
    }

    #[Route('/send/emergency', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'content' => new OA\Property(property: 'content', type: 'string'),
                'targetGroups' => new OA\Property(
                    property: 'targetGroups', 
                    type: 'array', 
                    items: new OA\Items(type: 'string', enum: ['students', 'parents', 'teachers'])
                )
            ]
        )
    )]
    public function sendEmergencyAlert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            return $this->json(['error' => 'Title and content are required'], 400);
        }

        $notifications = $this->alertService->sendEmergencyAlert(
            $data['title'],
            $data['content'],
            $data['targetGroups'] ?? ['students', 'parents', 'teachers']
        );

        return $this->json([
            'message' => 'Emergency alert sent successfully',
            'sent_count' => count($notifications)
        ], 200);
    }

    #[Route('/user/{userId}/unread-count', methods: ['GET'])]
    #[OA\Tag(name: 'alerts')]
    public function getUnreadCount(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $count = $this->alertService->getUnreadCount($user);

        return $this->json(['unread_count' => $count], 200);
    }

    #[Route('/user/{userId}/recent', methods: ['GET'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\Parameter(name: 'limit', description: 'Number of recent alerts to retrieve', in: 'query')]
    public function getRecentAlerts(int $userId, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $limit = (int) $request->query->get('limit', 10);
        $alerts = $this->alertService->getRecentAlerts($user, $limit);

        return $this->json($alerts, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/mark-read/{notificationId}', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'userId' => new OA\Property(property: 'userId', type: 'integer')
            ]
        )
    )]
    public function markAsRead(int $notificationId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['userId'])) {
            return $this->json(['error' => 'User ID is required'], 400);
        }

        $user = $this->userRepository->find($data['userId']);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $success = $this->alertService->markAsRead($notificationId, $user);

        if ($success) {
            return $this->json(['message' => 'Notification marked as read'], 200);
        } else {
            return $this->json(['error' => 'Notification not found or not accessible'], 404);
        }
    }

    #[Route('/mark-multiple-read', methods: ['POST'])]
    #[OA\Tag(name: 'alerts')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'userId' => new OA\Property(property: 'userId', type: 'integer'),
                'notificationIds' => new OA\Property(property: 'notificationIds', type: 'array', items: new OA\Items(type: 'integer'))
            ]
        )
    )]
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['userId']) || !isset($data['notificationIds'])) {
            return $this->json(['error' => 'User ID and notification IDs are required'], 400);
        }

        $user = $this->userRepository->find($data['userId']);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $count = $this->alertService->markMultipleAsRead($data['notificationIds'], $user);

        return $this->json([
            'message' => 'Notifications marked as read',
            'marked_count' => $count
        ], 200);
    }
}