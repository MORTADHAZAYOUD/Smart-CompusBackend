<?php

// src/Entity/Seance.php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    private string $type; // cours, devoir, examen

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "boolean")]
    private bool $presentiel;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $visioLink = null;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: "seances")]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    private ?Teacher $enseignant = null;
    #[ORM\ManyToOne(targetEntity: Matiere::class, inversedBy: 'seances')]
    #[Groups(['seance:read'])]
    private ?Matiere $matiere = null;
    public function getMatiere(): ?Matiere { return $this->matiere; }
    public function setMatiere(?Matiere $matiere): self { $this->matiere = $matiere; return $this; }


}