<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Entity\User;
use App\Repository\SeanceRepository;
use App\Repository\EvenementRepository;
use App\Repository\UserRepository;
use App\Repository\ClasseRepository;
use App\Service\AlertService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/calendar')]
class CalenderController extends AbstractController
{
    public function __construct(
        private SeanceRepository $seanceRepository,
        private EvenementRepository $evenementRepository,
        private UserRepository $userRepository,
        private ClasseRepository $classeRepository,
        private EntityManagerInterface $em,
        private AlertService $alertService,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    #[OA\Parameter(name: 'start', description: 'Start date (YYYY-MM-DD)', in: 'query')]
    #[OA\Parameter(name: 'end', description: 'End date (YYYY-MM-DD)', in: 'query')]
    #[OA\Parameter(name: 'user_id', description: 'User ID to filter events', in: 'query')]
    #[OA\Parameter(name: 'type', description: 'Event type filter', in: 'query')]
    public function getCalendarEvents(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        $userId = $request->query->get('user_id');
        $type = $request->query->get('type');

        $user = null;
        if ($userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }
        }

        // Get events within date range
        $events = [];
        
        if ($start && $end) {
            $startDate = new \DateTime($start);
            $endDate = new \DateTime($end);
            
            if ($type) {
                $events = $this->evenementRepository->findByType($type, $user);
                $events = array_filter($events, function($event) use ($startDate, $endDate) {
                    return $event->getDate() >= $startDate && $event->getDate() <= $endDate;
                });
            } else {
                $events = $this->evenementRepository->findByDateRange($startDate, $endDate, $user);
            }
        } else if ($user) {
            $events = $this->evenementRepository->findByUser($user);
        } else {
            $events = $this->evenementRepository->findPublicEvents();
        }

        // Format events for calendar display
        $calendarEvents = [];
        foreach ($events as $event) {
            $calendarEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getTitre(),
                'start' => $event->getDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndDate() ? $event->getEndDate()->format('Y-m-d H:i:s') : null,
                'allDay' => $event->isAllDay(),
                'description' => $event->getDescription(),
                'type' => $event->getType(),
                'priority' => $event->getPriority(),
                'location' => $event->getLocation(),
                'color' => $event->getColor(),
                'isPublic' => $event->isPublic(),
                'creator' => $event->getCreator() ? [
                    'id' => $event->getCreator()->getId(),
                    'name' => $event->getCreator()->getFirstname() . ' ' . $event->getCreator()->getLastname()
                ] : null,
                'class' => $event->getClasse() ? [
                    'id' => $event->getClasse()->getId(),
                    'name' => $event->getClasse()->getNom()
                ] : null,
                'subject' => $event->getMatiere() ? [
                    'id' => $event->getMatiere()->getId(),
                    'name' => $event->getMatiere()->getNom()
                ] : null
            ];
        }

        return $this->json($calendarEvents, 200);
    }

    #[Route('/events', methods: ['POST'])]
    #[OA\Tag(name: 'calendar')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'description' => new OA\Property(property: 'description', type: 'string'),
                'date' => new OA\Property(property: 'date', type: 'string', format: 'date-time'),
                'endDate' => new OA\Property(property: 'endDate', type: 'string', format: 'date-time'),
                'type' => new OA\Property(property: 'type', type: 'string'),
                'priority' => new OA\Property(property: 'priority', type: 'string'),
                'location' => new OA\Property(property: 'location', type: 'string'),
                'isPublic' => new OA\Property(property: 'isPublic', type: 'boolean'),
                'isAllDay' => new OA\Property(property: 'isAllDay', type: 'boolean'),
                'color' => new OA\Property(property: 'color', type: 'string'),
                'creatorId' => new OA\Property(property: 'creatorId', type: 'integer'),
                'classeId' => new OA\Property(property: 'classeId', type: 'integer'),
                'matiereId' => new OA\Property(property: 'matiereId', type: 'integer'),
                'attendeeIds' => new OA\Property(property: 'attendeeIds', type: 'array'),
                'isRecurring' => new OA\Property(property: 'isRecurring', type: 'boolean'),
                'recurringPattern' => new OA\Property(property: 'recurringPattern', type: 'string'),
                'recurringEndDate' => new OA\Property(property: 'recurringEndDate', type: 'string', format: 'date-time')
            ]
        )
    )]
    public function createEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['title']) || !isset($data['date']) || !isset($data['creatorId'])) {
            return $this->json(['error' => 'Title, date, and creator ID are required'], 400);
        }

        $creator = $this->userRepository->find($data['creatorId']);
        if (!$creator) {
            return $this->json(['error' => 'Creator not found'], 404);
        }

        $event = new Evenement();
        $event->setTitre($data['title']);
        $event->setDescription($data['description'] ?? '');
        $event->setDate(new \DateTime($data['date']));
        $event->setCreator($creator);
        $event->setType($data['type'] ?? 'general');
        $event->setPriority($data['priority'] ?? 'normale');
        $event->setLocation($data['location'] ?? null);
        $event->setPublic($data['isPublic'] ?? false);
        $event->setAllDay($data['isAllDay'] ?? false);
        $event->setColor($data['color'] ?? null);
        $event->setRecurring($data['isRecurring'] ?? false);
        $event->setRecurringPattern($data['recurringPattern'] ?? null);

        if (isset($data['endDate'])) {
            $event->setEndDate(new \DateTime($data['endDate']));
        }

        if (isset($data['recurringEndDate'])) {
            $event->setRecurringEndDate(new \DateTime($data['recurringEndDate']));
        }

        if (isset($data['classeId'])) {
            $classe = $this->classeRepository->find($data['classeId']);
            if ($classe) {
                $event->setClasse($classe);
            }
        }

        if (isset($data['matiereId'])) {
            $matiere = $this->em->getRepository('App:Matiere')->find($data['matiereId']);
            if ($matiere) {
                $event->setMatiere($matiere);
            }
        }

        if (isset($data['attendeeIds']) && is_array($data['attendeeIds'])) {
            $event->setAttendees($data['attendeeIds']);
        }

        // Validate the entity
        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->persist($event);
        $this->em->flush();

        // Send notification if it's an exam or important event
        if (in_array($event->getType(), ['exam', 'vacation']) && $event->getClasse()) {
            $this->sendEventNotification($event);
        }

        return $this->json($event, 201, [], ['groups' => ['event:read']]);
    }

    #[Route('/events/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'calendar')]
    public function updateEvent(int $id, Request $request): JsonResponse
    {
        $event = $this->evenementRepository->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        // Update fields
        if (isset($data['title'])) {
            $event->setTitre($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['date'])) {
            $event->setDate(new \DateTime($data['date']));
        }
        if (isset($data['endDate'])) {
            $event->setEndDate(new \DateTime($data['endDate']));
        }
        if (isset($data['type'])) {
            $event->setType($data['type']);
        }
        if (isset($data['priority'])) {
            $event->setPriority($data['priority']);
        }
        if (isset($data['location'])) {
            $event->setLocation($data['location']);
        }
        if (isset($data['isPublic'])) {
            $event->setPublic($data['isPublic']);
        }
        if (isset($data['isAllDay'])) {
            $event->setAllDay($data['isAllDay']);
        }
        if (isset($data['color'])) {
            $event->setColor($data['color']);
        }
        if (isset($data['attendeeIds'])) {
            $event->setAttendees($data['attendeeIds']);
        }

        $event->setUpdatedAt(new \DateTime());

        $this->em->flush();

        return $this->json($event, 200, [], ['groups' => ['event:read']]);
    }

    #[Route('/events/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'calendar')]
    public function deleteEvent(int $id): JsonResponse
    {
        $event = $this->evenementRepository->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $this->em->remove($event);
        $this->em->flush();

        return $this->json(['message' => 'Event deleted successfully'], 200);
    }

    #[Route('/user/{userId}/month/{year}/{month}', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    public function getUserMonthlyCalendar(int $userId, int $year, int $month): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $events = $this->evenementRepository->findByMonth($year, $month, $user);

        return $this->json($events, 200, [], ['groups' => ['event:read']]);
    }

    #[Route('/exams/upcoming/{userId}', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    #[OA\Parameter(name: 'limit', description: 'Number of upcoming exams to retrieve', in: 'query')]
    public function getUpcomingExams(int $userId, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $limit = (int) $request->query->get('limit', 10);
        $exams = $this->evenementRepository->findUpcomingExams($user, $limit);

        return $this->json($exams, 200, [], ['groups' => ['event:read']]);
    }

    #[Route('/vacations', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    #[OA\Parameter(name: 'start', description: 'Start date filter', in: 'query')]
    #[OA\Parameter(name: 'end', description: 'End date filter', in: 'query')]
    public function getVacations(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        $startDate = $start ? new \DateTime($start) : null;
        $endDate = $end ? new \DateTime($end) : null;

        $vacations = $this->evenementRepository->findVacations($startDate, $endDate);

        return $this->json($vacations, 200, [], ['groups' => ['event:read']]);
    }

    #[Route('/search', methods: ['GET'])]
    #[OA\Tag(name: 'calendar')]
    #[OA\Parameter(name: 'q', description: 'Search query', in: 'query')]
    #[OA\Parameter(name: 'user_id', description: 'User ID for filtering', in: 'query')]
    public function searchEvents(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        $userId = $request->query->get('user_id');
        
        if (!$query) {
            return $this->json(['error' => 'Search query is required'], 400);
        }

        $user = null;
        if ($userId) {
            $user = $this->userRepository->find($userId);
        }

        $events = $this->evenementRepository->searchEvents($query, $user);

        return $this->json($events, 200, [], ['groups' => ['event:read']]);
    }

    private function sendEventNotification(Evenement $event): void
    {
        if ($event->getType() === 'exam' && $event->getClasse()) {
            $this->alertService->sendExamAlert(
                $event->getTitre(),
                $event->getDate(),
                $event->getClasse(),
                $event->getMatiere() ? $event->getMatiere()->getNom() : null,
                $event->getLocation()
            );
        } elseif ($event->getType() === 'vacation') {
            $endDate = $event->getEndDate() ?? $event->getDate();
            $classes = $event->getClasse() ? [$event->getClasse()] : [];
            
            $this->alertService->sendVacationAlert(
                $event->getTitre(),
                $event->getDate(),
                $endDate,
                $classes
            );
        }

        $event->setNotificationSent(true);
        $this->em->flush();
    }
}