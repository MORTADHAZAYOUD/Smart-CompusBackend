<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    private ?Conversation $conversation = null;

    // Relation polymorphe avec les utilisateurs
    #[ORM\Column(type: "integer")]
    private ?int $emetteurId = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $emetteurType = null; // 'administrator', 'parent', 'teacher', 'student'

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date;

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

    public function getEmetteurId(): ?int
    {
        return $this->emetteurId;
    }

    public function setEmetteurId(?int $emetteurId): static
    {
        $this->emetteurId = $emetteurId;
        return $this;
    }

    public function getEmetteurType(): ?string
    {
        return $this->emetteurType;
    }

    public function setEmetteurType(?string $emetteurType): static
    {
        $this->emetteurType = $emetteurType;
        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    // Méthode helper pour définir l'émetteur
    public function setEmetteur(UserEntityInterface $user): static
    {
        $this->emetteurId = $user->getId();
        
        // Détermine le type basé sur la classe
        switch (get_class($user)) {
            case Administrator::class:
                $this->emetteurType = 'administrator';
                break;
            case ParentUser::class:
                $this->emetteurType = 'parent';
                break;
            case Teacher::class:
                $this->emetteurType = 'teacher';
                break;
            case Student::class:
                $this->emetteurType = 'student';
                break;
        }
        
        return $this;
    }
}