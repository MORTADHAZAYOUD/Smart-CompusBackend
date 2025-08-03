<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "float")]
    private float $valeur;

    #[ORM\ManyToOne(targetEntity: Student::class)]
    private ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Seance::class)]
    private ?Seance $seance = null;
    
    #[ORM\ManyToOne(targetEntity: Matiere::class, inversedBy: 'notes')]
    #[Groups(['note:read'])]
    private ?Matiere $matiere = null;
    public function getMatiere(): ?Matiere { return $this->matiere; }
    public function setMatiere(?Matiere $matiere): self { $this->matiere = $matiere; return $this; }

}