<?php

namespace App\Controller\Api;
use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// 11. ConversationController - Gestion des conversations
#[Route('/api/conversations')]
class ConversationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        $conversations = $this->entityManager->getRepository(Conversation::class)
            ->findConversationsForUser($user);
        
        return $this->json($conversations);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $conversation = new Conversation();
        $conversation->setTitre($data['titre']);
        $conversation->setDateCreation(new \DateTime());
        $conversation->setActive(true);
        
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();
        
        return $this->json($conversation, 201);
    }

    #[Route('/{id}/participants', methods: ['POST'])]
    public function addParticipant(Request $request, Conversation $conversation): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $conversation->addParticipant($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Participant ajouté avec succès']);
    }
}
