<?php

namespace App\Entity;

use App\Repository\TimetableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TimetableRepository::class)]
class Timetable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['timetable:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?\DateTime $endTime = null;

    #[ORM\Column(length: 20)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $dayOfWeek = null;

    #[ORM\Column(length: 50)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $type = null; // class, exam, meeting, etc.

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['timetable:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Classe::class)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?Matiere $matiere = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $location = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['timetable:read'])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['timetable:read'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?bool $isRecurring = false;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['timetable:read', 'timetable:write'])]
    private ?string $recurringPattern = null; // weekly, monthly, etc.

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isRecurring(): ?bool
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
}