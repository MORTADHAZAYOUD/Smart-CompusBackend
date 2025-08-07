<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\OneToMany(mappedBy: "conversation", targetEntity: ConversationParticipant::class, cascade: ["persist", "remove"])]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(UserEntityInterface $user): static
    {
        // Vérifier si l'utilisateur est déjà participant
        $exists = $this->participants->exists(function($key, $participant) use ($user) {
            return $participant->getUserId() === $user->getId() && 
                   $participant->getUserType() === $this->getUserTypeFromClass($user);
        });

        if (!$exists) {
            $participant = new ConversationParticipant();
            $participant->setConversation($this);
            $participant->setUser($user);
            $this->participants->add($participant);
        }
        
        return $this;
    }

    public function removeParticipant(UserEntityInterface $user): static
    {
        $this->participants->removeElement(
            $this->participants->findFirst(function($key, $participant) use ($user) {
                return $participant->getUserId() === $user->getId() && 
                       $participant->getUserType() === $this->getUserTypeFromClass($user);
            })
        );
        
        return $this;
    }

    private function getUserTypeFromClass(UserEntityInterface $user): string
    {
        switch (get_class($user)) {
            case Administrator::class:
                return 'administrator';
            case ParentUser::class:
                return 'parent';
            case Teacher::class:
                return 'teacher';
            case Student::class:
                return 'student';
            default:
                throw new \InvalidArgumentException('Unknown user type');
        }
    }
}
