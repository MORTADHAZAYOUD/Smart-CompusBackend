<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\ParentUser;
use App\Entity\Classe;
use App\Repository\UserRepository;
use App\Repository\StudentRepository;
use App\Repository\ParentUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlertService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private StudentRepository $studentRepository,
        private ParentUserRepository $parentRepository
    ) {}

    public function sendAlert(
        string $title,
        string $content,
        array $recipients = [],
        string $priority = 'normale',
        string $type = 'general'
    ): array {
        $notifications = [];
        
        foreach ($recipients as $recipient) {
            if ($recipient instanceof User) {
                $notification = $this->createNotification($title, $content, $recipient, $priority, $type);
                $notifications[] = $notification;
            }
        }
        
        $this->em->flush();
        
        return $notifications;
    }

    public function sendAlertToStudents(
        string $title,
        string $content,
        array $studentIds = [],
        string $priority = 'normale',
        string $type = 'student_alert'
    ): array {
        $students = empty($studentIds) 
            ? $this->studentRepository->findAll()
            : $this->studentRepository->findBy(['id' => $studentIds]);
        
        return $this->sendAlert($title, $content, $students, $priority, $type);
    }

    public function sendAlertToParents(
        string $title,
        string $content,
        array $parentIds = [],
        string $priority = 'normale',
        string $type = 'parent_alert'
    ): array {
        $parents = empty($parentIds) 
            ? $this->parentRepository->findAll()
            : $this->parentRepository->findBy(['id' => $parentIds]);
        
        return $this->sendAlert($title, $content, $parents, $priority, $type);
    }

    public function sendAlertToClass(
        string $title,
        string $content,
        Classe $classe,
        bool $includeParents = true,
        string $priority = 'normale',
        string $type = 'class_alert'
    ): array {
        $students = $classe->getStudent()->toArray();
        $recipients = $students;
        
        if ($includeParents) {
            foreach ($students as $student) {
                if ($student->getParent()) {
                    $recipients[] = $student->getParent();
                }
            }
        }
        
        return $this->sendAlert($title, $content, $recipients, $priority, $type);
    }

    public function sendExamAlert(
        string $examTitle,
        \DateTime $examDate,
        Classe $classe,
        string $subject = null,
        string $location = null
    ): array {
        $content = "Un examen est prévu: {$examTitle}\n";
        $content .= "Date: " . $examDate->format('d/m/Y H:i') . "\n";
        if ($subject) {
            $content .= "Matière: {$subject}\n";
        }
        if ($location) {
            $content .= "Lieu: {$location}\n";
        }
        
        return $this->sendAlertToClass(
            "Examen: {$examTitle}",
            $content,
            $classe,
            true,
            'haute',
            'exam_alert'
        );
    }

    public function sendVacationAlert(
        string $vacationName,
        \DateTime $startDate,
        \DateTime $endDate,
        array $classes = []
    ): array {
        $content = "Période de vacances: {$vacationName}\n";
        $content .= "Du " . $startDate->format('d/m/Y') . " au " . $endDate->format('d/m/Y');
        
        $notifications = [];
        
        if (empty($classes)) {
            // Send to all students and parents
            $students = $this->studentRepository->findAll();
            foreach ($students as $student) {
                $recipients = [$student];
                if ($student->getParent()) {
                    $recipients[] = $student->getParent();
                }
                $notifications = array_merge(
                    $notifications,
                    $this->sendAlert("Vacances: {$vacationName}", $content, $recipients, 'normale', 'vacation_alert')
                );
            }
        } else {
            foreach ($classes as $classe) {
                $notifications = array_merge(
                    $notifications,
                    $this->sendAlertToClass("Vacances: {$vacationName}", $content, $classe, true, 'normale', 'vacation_alert')
                );
            }
        }
        
        return $notifications;
    }

    public function sendTimetableChangeAlert(
        string $changeDescription,
        \DateTime $affectedDate,
        array $affectedUsers = []
    ): array {
        $content = "Modification d'emploi du temps:\n{$changeDescription}\n";
        $content .= "Date concernée: " . $affectedDate->format('d/m/Y');
        
        return $this->sendAlert(
            "Changement d'emploi du temps",
            $content,
            $affectedUsers,
            'haute',
            'timetable_change'
        );
    }

    public function sendEmergencyAlert(
        string $title,
        string $content,
        array $targetGroups = ['students', 'parents', 'teachers']
    ): array {
        $recipients = [];
        
        if (in_array('students', $targetGroups)) {
            $recipients = array_merge($recipients, $this->studentRepository->findAll());
        }
        
        if (in_array('parents', $targetGroups)) {
            $recipients = array_merge($recipients, $this->parentRepository->findAll());
        }
        
        if (in_array('teachers', $targetGroups)) {
            $teachers = $this->em->getRepository('App:Teacher')->findAll();
            $recipients = array_merge($recipients, $teachers);
        }
        
        return $this->sendAlert($title, $content, $recipients, 'urgente', 'emergency');
    }

    public function sendPersonalAlert(
        string $title,
        string $content,
        User $user,
        string $priority = 'normale'
    ): Notification {
        return $this->createNotification($title, $content, $user, $priority, 'personal');
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = $this->em->getRepository(Notification::class)->findOneBy([
            'id' => $notificationId,
            'destinataire' => $user
        ]);
        
        if ($notification) {
            $notification->setLu(true);
            $notification->setVue(true);
            $this->em->flush();
            return true;
        }
        
        return false;
    }

    public function markMultipleAsRead(array $notificationIds, User $user): int
    {
        $notifications = $this->em->getRepository(Notification::class)->findBy([
            'id' => $notificationIds,
            'destinataire' => $user
        ]);
        
        $count = 0;
        foreach ($notifications as $notification) {
            $notification->setLu(true);
            $notification->setVue(true);
            $count++;
        }
        
        $this->em->flush();
        return $count;
    }

    public function getUnreadCount(User $user): int
    {
        return $this->em->getRepository(Notification::class)->count([
            'destinataire' => $user,
            'lu' => false
        ]);
    }

    public function getRecentAlerts(User $user, int $limit = 10): array
    {
        return $this->em->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('n.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function createNotification(
        string $title,
        string $content,
        User $recipient,
        string $priority,
        string $type
    ): Notification {
        $notification = new Notification();
        $notification->setTitre($title);
        $notification->setContenu($content);
        $notification->setDestinataire($recipient);
        $notification->setPriorite($priority);
        $notification->setDateCreation(new \DateTime());
        $notification->setLu(false);
        $notification->setVue(false);
        
        $this->em->persist($notification);
        
        return $notification;
    }
}