<?php

namespace App\Controller\Api;

use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClasseRepository;
use App\Repository\ParentUserRepository;
use App\Repository\MessageRepository;
use App\Repository\NotificationRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard')]
class DashboardApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private StudentRepository $studentRepository,
        private TeacherRepository $teacherRepository,
        private ClasseRepository $classeRepository,
        private ParentUserRepository $parentRepository,
        private MessageRepository $messageRepository,
        private NotificationRepository $notificationRepository,
        private SeanceRepository $seanceRepository
    ) {}

    #[Route('/stats', methods: ['GET'])]
    #[OA\Tag(name: 'dashboard')]
    #[OA\Response(response: 200, description: 'Dashboard statistics')]
    #[IsGranted('ROLE_USER')]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        $roles = $user->getRoles();

        // Basic counts
        $stats = [
            'totalStudents' => $this->studentRepository->count([]),
            'totalTeachers' => $this->teacherRepository->count([]),
            'totalClasses' => $this->classeRepository->count([]),
            'totalParents' => $this->parentRepository->count([]),
        ];

        // Role-specific stats
        if (in_array('ROLE_ADMIN', $roles)) {
            $stats['adminStats'] = $this->getAdminStats();
        } elseif (in_array('ROLE_TEACHER', $roles)) {
            $stats['teacherStats'] = $this->getTeacherStats($user);
        } elseif (in_array('ROLE_STUDENT', $roles)) {
            $stats['studentStats'] = $this->getStudentStats($user);
        } elseif (in_array('ROLE_PARENT', $roles)) {
            $stats['parentStats'] = $this->getParentStats($user);
        }

        $stats['recentActivity'] = $this->getRecentActivity($user);
        $stats['notifications'] = $this->getUnreadNotifications($user);

        return $this->json($stats);
    }

    #[Route('/recent-activity', methods: ['GET'])]
    #[OA\Tag(name: 'dashboard')]
    #[OA\Response(response: 200, description: 'Recent activity')]
    #[IsGranted('ROLE_USER')]
    public function getRecentActivity(): JsonResponse
    {
        $user = $this->getUser();
        $activity = $this->getRecentActivity($user);

        return $this->json($activity);
    }

    #[Route('/notifications', methods: ['GET'])]
    #[OA\Tag(name: 'dashboard')]
    #[OA\Response(response: 200, description: 'User notifications')]
    #[IsGranted('ROLE_USER')]
    public function getNotifications(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findBy(
            ['destinataire' => $user],
            ['dateCreation' => 'DESC'],
            10
        );

        return $this->json($notifications, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/quick-stats', methods: ['GET'])]
    #[OA\Tag(name: 'dashboard')]
    #[OA\Response(response: 200, description: 'Quick statistics for cards')]
    #[IsGranted('ROLE_USER')]
    public function getQuickStats(): JsonResponse
    {
        $today = new \DateTime();
        $thisWeek = (clone $today)->modify('monday this week');
        $thisMonth = (clone $today)->modify('first day of this month');

        return $this->json([
            'today' => [
                'date' => $today->format('Y-m-d'),
                'sessions' => $this->getSessionsCount($today, $today),
                'messages' => $this->getMessagesCount($today, $today),
            ],
            'thisWeek' => [
                'start' => $thisWeek->format('Y-m-d'),
                'end' => (clone $thisWeek)->modify('+6 days')->format('Y-m-d'),
                'sessions' => $this->getSessionsCount($thisWeek, (clone $thisWeek)->modify('+6 days')),
                'messages' => $this->getMessagesCount($thisWeek, (clone $thisWeek)->modify('+6 days')),
            ],
            'thisMonth' => [
                'start' => $thisMonth->format('Y-m-d'),
                'end' => (clone $thisMonth)->modify('last day of this month')->format('Y-m-d'),
                'sessions' => $this->getSessionsCount($thisMonth, (clone $thisMonth)->modify('last day of this month')),
                'messages' => $this->getMessagesCount($thisMonth, (clone $thisMonth)->modify('last day of this month')),
            ]
        ]);
    }

    private function getAdminStats(): array
    {
        return [
            'totalUsers' => $this->studentRepository->count([]) + $this->teacherRepository->count([]) + $this->parentRepository->count([]),
            'activeClasses' => $this->classeRepository->count([]),
            'totalMessages' => $this->messageRepository->count([]),
            'totalNotifications' => $this->notificationRepository->count([]),
            'recentRegistrations' => $this->getRecentRegistrations(),
        ];
    }

    private function getTeacherStats($teacher): array
    {
        return [
            'myClasses' => count($teacher->getSeances()),
            'totalStudents' => $this->getTotalStudentsForTeacher($teacher),
            'todaySessions' => $this->getTodaySessionsForTeacher($teacher),
            'unreadMessages' => $this->getUnreadMessagesCount($teacher),
        ];
    }

    private function getStudentStats($student): array
    {
        return [
            'myClass' => $student->getClasse()?->getNom() ?? 'No class assigned',
            'classmates' => $student->getClasse() ? count($student->getClasse()->getStudents()) - 1 : 0,
            'todaySessions' => $this->getTodaySessionsForStudent($student),
            'unreadMessages' => $this->getUnreadMessagesCount($student),
        ];
    }

    private function getParentStats($parent): array
    {
        $children = $parent->getEnfants();
        
        return [
            'totalChildren' => count($children),
            'childrenClasses' => $this->getChildrenClasses($children),
            'unreadMessages' => $this->getUnreadMessagesCount($parent),
        ];
    }

    private function getRecentActivity($user): array
    {
        $activities = [];
        
        // Recent messages
        $recentMessages = $this->messageRepository->findBy(
            ['emetteur' => $user],
            ['date' => 'DESC'],
            5
        );

        foreach ($recentMessages as $message) {
            $activities[] = [
                'type' => 'message',
                'title' => 'Message sent',
                'description' => substr($message->getContenu(), 0, 100) . '...',
                'date' => $message->getDate(),
                'icon' => 'message'
            ];
        }

        // Recent notifications
        $recentNotifications = $this->notificationRepository->findBy(
            ['destinataire' => $user],
            ['dateCreation' => 'DESC'],
            3
        );

        foreach ($recentNotifications as $notification) {
            $activities[] = [
                'type' => 'notification',
                'title' => $notification->getTitre(),
                'description' => $notification->getMessage(),
                'date' => $notification->getDateCreation(),
                'icon' => 'bell',
                'read' => $notification->isLu()
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($activities, 0, 10);
    }

    private function getUnreadNotifications($user): array
    {
        return $this->notificationRepository->findBy([
            'destinataire' => $user,
            'lu' => false
        ], ['dateCreation' => 'DESC'], 5);
    }

    private function getSessionsCount(\DateTime $start, \DateTime $end): int
    {
        return $this->seanceRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dateDebut >= :start')
            ->andWhere('s.dateDebut <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getMessagesCount(\DateTime $start, \DateTime $end): int
    {
        return $this->messageRepository->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.date >= :start')
            ->andWhere('m.date <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getRecentRegistrations(): array
    {
        $oneWeekAgo = new \DateTime('-1 week');
        
        return $this->studentRepository->createQueryBuilder('s')
            ->where('s.id > :id')
            ->setParameter('id', 0)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    private function getTotalStudentsForTeacher($teacher): int
    {
        // This would need to be implemented based on your business logic
        // For now, returning 0
        return 0;
    }

    private function getTodaySessionsForTeacher($teacher): int
    {
        $today = new \DateTime();
        return $this->seanceRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.teacher = :teacher')
            ->andWhere('DATE(s.dateDebut) = :today')
            ->setParameter('teacher', $teacher)
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getTodaySessionsForStudent($student): int
    {
        $today = new \DateTime();
        return $this->seanceRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.classe = :classe')
            ->andWhere('DATE(s.dateDebut) = :today')
            ->setParameter('classe', $student->getClasse())
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUnreadMessagesCount($user): int
    {
        // This would need to be implemented based on your message reading logic
        return 0;
    }

    private function getChildrenClasses($children): array
    {
        $classes = [];
        foreach ($children as $child) {
            if ($child->getClasse()) {
                $classes[] = $child->getClasse()->getNom();
            }
        }
        return array_unique($classes);
    }
}