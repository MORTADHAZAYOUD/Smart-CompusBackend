<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MatiereRepository::class)]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['matiere:read', 'seance:read', 'note:read'])]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    #[Groups(['matiere:read', 'seance:read', 'note:read'])]
    private string $nom;

    #[ORM\OneToMany(mappedBy: 'matiere', targetEntity: Seance::class)]
    #[Groups(['matiere:read'])]
    private Collection $seances;

    #[ORM\OneToMany(mappedBy: 'matiere', targetEntity: Note::class)]
    #[Groups(['matiere:read'])]
    private Collection $notes;

    public function __construct()
    {
        $this->seances = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getSeances(): Collection { return $this->seances; }
    public function addSeance(Seance $seance): self
    {
        if (!$this->seances->contains($seance)) {
            $this->seances[] = $seance;
            $seance->setMatiere($this);
        }
        return $this;
    }

    public function removeSeance(Seance $seance): self
    {
        if ($this->seances->removeElement($seance)) {
            if ($seance->getMatiere() === $this) {
                $seance->setMatiere(null);
            }
        }
        return $this;
    }

    public function getNotes(): Collection { return $this->notes; }
    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setMatiere($this);
        }
        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getMatiere() === $this) {
                $note->setMatiere(null);
            }
        }
        return $this;
    }
}