<?php

namespace App\Controller\Api;

use App\Entity\Timetable;
use App\Entity\User;
use App\Entity\Classe;
use App\Repository\TimetableRepository;
use App\Repository\UserRepository;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/timetables')]
class TimetableController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TimetableRepository $timetableRepository,
        private UserRepository $userRepository,
        private ClasseRepository $classeRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'timetable')]
    #[OA\Parameter(name: 'user_id', description: 'Filter by user ID', in: 'query')]
    #[OA\Parameter(name: 'classe_id', description: 'Filter by class ID', in: 'query')]
    #[OA\Parameter(name: 'day', description: 'Filter by day of week', in: 'query')]
    #[OA\Parameter(name: 'type', description: 'Filter by type', in: 'query')]
    public function getTimetables(Request $request): JsonResponse
    {
        $userId = $request->query->get('user_id');
        $classeId = $request->query->get('classe_id');
        $day = $request->query->get('day');
        $type = $request->query->get('type');

        $queryBuilder = $this->timetableRepository->createQueryBuilder('t');

        if ($userId) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                $queryBuilder->andWhere('t.user = :user')->setParameter('user', $user);
            }
        }

        if ($classeId) {
            $classe = $this->classeRepository->find($classeId);
            if ($classe) {
                $queryBuilder->andWhere('t.classe = :classe')->setParameter('classe', $classe);
            }
        }

        if ($day) {
            $queryBuilder->andWhere('t.dayOfWeek = :day')->setParameter('day', $day);
        }

        if ($type) {
            $queryBuilder->andWhere('t.type = :type')->setParameter('type', $type);
        }

        $timetables = $queryBuilder->orderBy('t.startTime', 'ASC')->getQuery()->getResult();

        return $this->json($timetables, 200, [], ['groups' => ['timetable:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'timetable')]
    public function getTimetable(int $id): JsonResponse
    {
        $timetable = $this->timetableRepository->find($id);
        
        if (!$timetable) {
            return $this->json(['error' => 'Timetable not found'], 404);
        }

        return $this->json($timetable, 200, [], ['groups' => ['timetable:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'timetable')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'title' => new OA\Property(property: 'title', type: 'string'),
                'description' => new OA\Property(property: 'description', type: 'string'),
                'startTime' => new OA\Property(property: 'startTime', type: 'string', format: 'date-time'),
                'endTime' => new OA\Property(property: 'endTime', type: 'string', format: 'date-time'),
                'dayOfWeek' => new OA\Property(property: 'dayOfWeek', type: 'string'),
                'type' => new OA\Property(property: 'type', type: 'string'),
                'userId' => new OA\Property(property: 'userId', type: 'integer'),
                'classeId' => new OA\Property(property: 'classeId', type: 'integer'),
                'matiereId' => new OA\Property(property: 'matiereId', type: 'integer'),
                'location' => new OA\Property(property: 'location', type: 'string'),
                'isRecurring' => new OA\Property(property: 'isRecurring', type: 'boolean'),
                'recurringPattern' => new OA\Property(property: 'recurringPattern', type: 'string')
            ]
        )
    )]
    public function createTimetable(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $timetable = new Timetable();
        
        // Set basic properties
        $timetable->setTitle($data['title'] ?? '');
        $timetable->setDescription($data['description'] ?? null);
        $timetable->setDayOfWeek($data['dayOfWeek'] ?? '');
        $timetable->setType($data['type'] ?? 'class');
        $timetable->setLocation($data['location'] ?? null);
        $timetable->setRecurring($data['isRecurring'] ?? false);
        $timetable->setRecurringPattern($data['recurringPattern'] ?? null);

        // Set dates
        if (isset($data['startTime'])) {
            $timetable->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $timetable->setEndTime(new \DateTime($data['endTime']));
        }

        // Set user
        if (isset($data['userId'])) {
            $user = $this->userRepository->find($data['userId']);
            if ($user) {
                $timetable->setUser($user);
            }
        }

        // Set class
        if (isset($data['classeId'])) {
            $classe = $this->classeRepository->find($data['classeId']);
            if ($classe) {
                $timetable->setClasse($classe);
            }
        }

        // Set matiere
        if (isset($data['matiereId'])) {
            $matiere = $this->em->getRepository('App:Matiere')->find($data['matiereId']);
            if ($matiere) {
                $timetable->setMatiere($matiere);
            }
        }

        // Validate the entity
        $errors = $this->validator->validate($timetable);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->em->persist($timetable);
        $this->em->flush();

        return $this->json($timetable, 201, [], ['groups' => ['timetable:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'timetable')]
    public function updateTimetable(int $id, Request $request): JsonResponse
    {
        $timetable = $this->timetableRepository->find($id);
        
        if (!$timetable) {
            return $this->json(['error' => 'Timetable not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        // Update properties
        if (isset($data['title'])) {
            $timetable->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $timetable->setDescription($data['description']);
        }
        if (isset($data['dayOfWeek'])) {
            $timetable->setDayOfWeek($data['dayOfWeek']);
        }
        if (isset($data['type'])) {
            $timetable->setType($data['type']);
        }
        if (isset($data['location'])) {
            $timetable->setLocation($data['location']);
        }
        if (isset($data['isRecurring'])) {
            $timetable->setRecurring($data['isRecurring']);
        }
        if (isset($data['recurringPattern'])) {
            $timetable->setRecurringPattern($data['recurringPattern']);
        }

        // Update dates
        if (isset($data['startTime'])) {
            $timetable->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $timetable->setEndTime(new \DateTime($data['endTime']));
        }

        $timetable->setUpdatedAt(new \DateTime());

        $this->em->flush();

        return $this->json($timetable, 200, [], ['groups' => ['timetable:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'timetable')]
    public function deleteTimetable(int $id): JsonResponse
    {
        $timetable = $this->timetableRepository->find($id);
        
        if (!$timetable) {
            return $this->json(['error' => 'Timetable not found'], 404);
        }

        $this->em->remove($timetable);
        $this->em->flush();

        return $this->json(['message' => 'Timetable deleted successfully'], 200);
    }

    #[Route('/user/{userId}/week', methods: ['GET'])]
    #[OA\Tag(name: 'timetable')]
    #[OA\Parameter(name: 'week_start', description: 'Start of week (Y-m-d format)', in: 'query')]
    public function getUserWeeklyTimetable(int $userId, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $weekStart = $request->query->get('week_start');
        $startOfWeek = $weekStart ? new \DateTime($weekStart) : new \DateTime('monday this week');
        $startOfWeek->setTime(0, 0, 0);

        $timetables = $this->timetableRepository->findWeeklyScheduleByUser($user, $startOfWeek);

        return $this->json($timetables, 200, [], ['groups' => ['timetable:read']]);
    }

    #[Route('/admin/bulk', methods: ['POST'])]
    #[OA\Tag(name: 'admin')]
    #[OA\RequestBody(
        description: 'Bulk create timetables for multiple users or classes',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'timetables' => new OA\Property(property: 'timetables', type: 'array'),
                'applyToClass' => new OA\Property(property: 'applyToClass', type: 'boolean'),
                'classeId' => new OA\Property(property: 'classeId', type: 'integer')
            ]
        )
    )]
    public function bulkCreateTimetables(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['timetables'])) {
            return $this->json(['error' => 'Invalid data structure'], 400);
        }

        $createdTimetables = [];
        $errors = [];

        foreach ($data['timetables'] as $timetableData) {
            try {
                $timetable = new Timetable();
                
                // Set properties
                $timetable->setTitle($timetableData['title'] ?? '');
                $timetable->setDescription($timetableData['description'] ?? null);
                $timetable->setDayOfWeek($timetableData['dayOfWeek'] ?? '');
                $timetable->setType($timetableData['type'] ?? 'class');
                $timetable->setLocation($timetableData['location'] ?? null);
                $timetable->setRecurring($timetableData['isRecurring'] ?? false);
                $timetable->setRecurringPattern($timetableData['recurringPattern'] ?? null);

                if (isset($timetableData['startTime'])) {
                    $timetable->setStartTime(new \DateTime($timetableData['startTime']));
                }
                if (isset($timetableData['endTime'])) {
                    $timetable->setEndTime(new \DateTime($timetableData['endTime']));
                }

                // Set user
                if (isset($timetableData['userId'])) {
                    $user = $this->userRepository->find($timetableData['userId']);
                    if ($user) {
                        $timetable->setUser($user);
                    }
                }

                // Set class
                if (isset($timetableData['classeId'])) {
                    $classe = $this->classeRepository->find($timetableData['classeId']);
                    if ($classe) {
                        $timetable->setClasse($classe);
                    }
                }

                $this->em->persist($timetable);
                $createdTimetables[] = $timetable;
                
            } catch (\Exception $e) {
                $errors[] = ['timetable' => $timetableData, 'error' => $e->getMessage()];
            }
        }

        $this->em->flush();

        return $this->json([
            'created' => count($createdTimetables),
            'errors' => $errors,
            'timetables' => $createdTimetables
        ], 201, [], ['groups' => ['timetable:read']]);
    }
}