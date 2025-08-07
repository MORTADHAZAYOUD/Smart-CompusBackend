<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'conversation_participant')]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: "participants")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\Column(type: "integer")]
    private ?int $userId = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $userType = null; // 'administrator', 'parent', 'teacher', 'student'

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $joinedAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(?string $userType): static
    {
        $this->userType = $userType;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    // Méthode helper pour définir l'utilisateur
    public function setUser(UserEntityInterface $user): static
    {
        $this->userId = $user->getId();
        
        // Détermine le type basé sur la classe
        switch (get_class($user)) {
            case Administrator::class:
                $this->userType = 'administrator';
                break;
            case ParentUser::class:
                $this->userType = 'parent';
                break;
            case Teacher::class:
                $this->userType = 'teacher';
                break;
            case Student::class:
                $this->userType = 'student';
                break;
        }
        
        return $this;
    }
}