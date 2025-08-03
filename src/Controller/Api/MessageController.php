<?php
// src/Controller/MessageController.php
namespace App\Controller\Api;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageRepository $messageRepository,
        private ConversationRepository $conversationRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/conversations', methods: ['GET'])]
    #[OA\Tag(name: 'message')]
    public function getUserConversations(): JsonResponse
    {
        $user = $this->getUser();
        $conversations = $this->conversationRepository->findByParticipant($user);
        
        return $this->json($conversations, 200, [], ['groups' => ['conversation:read']]);
    }

    #[Route('/conversation/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'message')]
    public function getConversationMessages(int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);
        if (!$conversation) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        $messages = $this->messageRepository->findBy(
            ['conversation' => $conversation],
            ['date' => 'ASC']
        );

        return $this->json($messages, 200, [], ['groups' => ['message:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'message')]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Créer ou récupérer la conversation
        if (isset($data['conversationId'])) {
            $conversation = $this->conversationRepository->find($data['conversationId']);
        } else {
            // Créer nouvelle conversation
            $conversation = new Conversation();
            $conversation->getParticipants()->add($user);
            
            if (isset($data['destinataireId'])) {
                $destinataire = $this->userRepository->find($data['destinataireId']);
                $conversation->getParticipants()->add($destinataire);
            }
            
            $this->em->persist($conversation);
        }

        $message = new Message();
        $message->setConversation($conversation)
                ->setEmetteur($user)
                ->setContenu($data['contenu'])
                ->setDate(new \DateTime());

        $this->em->persist($message);
        $this->em->flush();

        return $this->json($message, 201, [], ['groups' => ['message:read']]);
    }
}