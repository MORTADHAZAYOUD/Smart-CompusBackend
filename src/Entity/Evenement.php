<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(['event:read'])]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Groups(['event:read', 'event:write'])]
    private string $titre;

    #[ORM\Column(type: "datetime")]
    #[Groups(['event:read', 'event:write'])]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: "text")]
    #[Groups(['event:read', 'event:write'])]
    private string $description;

    #[ORM\Column(type: "string", length: 50)]
    #[Groups(['event:read', 'event:write'])]
    private string $type = 'general'; // exam, vacation, meeting, personal, general

    #[ORM\Column(type: "string", length: 20)]
    #[Groups(['event:read', 'event:write'])]
    private string $priority = 'normale'; // normale, haute, urgente

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $location = null;

    #[ORM\Column(type: "boolean")]
    #[Groups(['event:read', 'event:write'])]
    private bool $isPublic = false;

    #[ORM\Column(type: "boolean")]
    #[Groups(['event:read', 'event:write'])]
    private bool $isAllDay = false;

    #[ORM\Column(type: "string", length: 7, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $color = null; // Color for calendar display

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: Classe::class)]
    #[Groups(['event:read', 'event:write'])]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[Groups(['event:read', 'event:write'])]
    private ?Matiere $matiere = null;

    #[ORM\Column(type: "datetime")]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime")]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: "boolean")]
    #[Groups(['event:read', 'event:write'])]
    private bool $isRecurring = false;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $recurringPattern = null; // daily, weekly, monthly, yearly

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $recurringEndDate = null;

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?array $attendees = null; // Array of user IDs who should attend

    #[ORM\Column(type: "boolean")]
    #[Groups(['event:read', 'event:write'])]
    private bool $notificationSent = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->attendees = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
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

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    public function setAllDay(bool $isAllDay): static
    {
        $this->isAllDay = $isAllDay;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;
        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): static
    {
        $this->matiere = $matiere;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function setRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;
        return $this;
    }

    public function getRecurringPattern(): ?string
    {
        return $this->recurringPattern;
    }

    public function setRecurringPattern(?string $recurringPattern): static
    {
        $this->recurringPattern = $recurringPattern;
        return $this;
    }

    public function getRecurringEndDate(): ?\DateTimeInterface
    {
        return $this->recurringEndDate;
    }

    public function setRecurringEndDate(?\DateTimeInterface $recurringEndDate): static
    {
        $this->recurringEndDate = $recurringEndDate;
        return $this;
    }

    public function getAttendees(): ?array
    {
        return $this->attendees;
    }

    public function setAttendees(?array $attendees): static
    {
        $this->attendees = $attendees;
        return $this;
    }

    public function addAttendee(int $userId): static
    {
        if (!in_array($userId, $this->attendees ?? [])) {
            $this->attendees[] = $userId;
        }
        return $this;
    }

    public function removeAttendee(int $userId): static
    {
        $this->attendees = array_filter($this->attendees ?? [], fn($id) => $id !== $userId);
        return $this;
    }

    public function isNotificationSent(): bool
    {
        return $this->notificationSent;
    }

    public function setNotificationSent(bool $notificationSent): static
    {
        $this->notificationSent = $notificationSent;
        return $this;
    }
}