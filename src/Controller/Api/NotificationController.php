<?php

namespace App\Controller\Api;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// 12. NotificationController - Gestion des notifications
#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'notification')]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['destinataire' => $user], ['dateCreation' => 'DESC']);
        
        return $this->json($notifications);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'notification')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $notification = new Notification();
        $notification->setTitre($data['titre']);
        $notification->setContenu($data['contenu']);
        $notification->setDateCreation(new \DateTime());
        $notification->setLu(false);
        $notification->setPriorite($data['priorite'] ?? 'normale');
        
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
        
        return $this->json($notification, 201);
    }

    #[Route('/{id}/marquer-lu', methods: ['PUT'])]
    #[OA\Tag(name: 'notification')]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $notification->setLu(true);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Notification marquée comme lue']);
    }

    #[Route('/non-lues', methods: ['GET'])]
    #[OA\Tag(name: 'notification')]
    public function getUnreadNotifications(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['destinataire' => $user, 'lu' => false]);
        
        return $this->json(['count' => count($notifications), 'notifications' => $notifications]);
    }
}