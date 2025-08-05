<?php

namespace App\Entity;

use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateMarquage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    private ?User $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    private ?Seance $seance = null;

    public function __construct()
    {
        $this->dateMarquage = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDateMarquage(): ?\DateTimeInterface
    {
        return $this->dateMarquage;
    }

    public function setDateMarquage(\DateTimeInterface $dateMarquage): static
    {
        $this->dateMarquage = $dateMarquage;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getEtudiant(): ?User
    {
        return $this->etudiant;
    }

    public function setEtudiant(?User $etudiant): static
    {
        $this->etudiant = $etudiant;
        return $this;
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;
        return $this;
    }

    // Méthodes helper
    public function marquerPresence(): void
    {
        $this->status = 'present';
        $this->dateMarquage = new \DateTime();
    }

    public function justifierAbsence(): void
    {
        $this->status = 'absent_justifie';
    }
}