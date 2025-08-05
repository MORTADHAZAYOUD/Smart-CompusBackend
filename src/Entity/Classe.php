<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $niveau = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $effectif = null;

    #[ORM\ManyToOne(inversedBy: 'classesEnseignees')]
    private ?User $enseignant = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'classe')]
    private Collection $etudiants;

    /**
     * @var Collection<int, Seance>
     */
    #[ORM\OneToMany(targetEntity: Seance::class, mappedBy: 'classe')]
    private Collection $seances;

    /**
     * @var Collection<int, Calendrier>
     */
    #[ORM\OneToMany(targetEntity: Calendrier::class, mappedBy: 'classe')]
    private Collection $calendriers;

    public function __construct()
    {
        $this->etudiants = new ArrayCollection();
        $this->seances = new ArrayCollection();
        $this->calendriers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEffectif(): ?int
    {
        return $this->effectif;
    }

    public function setEffectif(int $effectif): static
    {
        $this->effectif = $effectif;
        return $this;
    }

    public function getEnseignant(): ?User
    {
        return $this->enseignant;
    }

    public function setEnseignant(?User $enseignant): static
    {
        $this->enseignant = $enseignant;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(User $etudiant): static
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants->add($etudiant);
            $etudiant->setClasse($this);
        }
        return $this;
    }

    public function removeEtudiant(User $etudiant): static
    {
        if ($this->etudiants->removeElement($etudiant)) {
            if ($etudiant->getClasse() === $this) {
                $etudiant->setClasse(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Seance>
     */
    public function getSeances(): Collection
    {
        return $this->seances;
    }

    public function addSeance(Seance $seance): static
    {
        if (!$this->seances->contains($seance)) {
            $this->seances->add($seance);
            $seance->setClasse($this);
        }
        return $this;
    }

    public function removeSeance(Seance $seance): static
    {
        if ($this->seances->removeElement($seance)) {
            if ($seance->getClasse() === $this) {
                $seance->setClasse(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Calendrier>
     */
    public function getCalendriers(): Collection
    {
        return $this->calendriers;
    }

    public function addCalendrier(Calendrier $calendrier): static
    {
        if (!$this->calendriers->contains($calendrier)) {
            $this->calendriers->add($calendrier);
            $calendrier->setClasse($this);
        }
        return $this;
    }

    public function removeCalendrier(Calendrier $calendrier): static
    {
        if ($this->calendriers->removeElement($calendrier)) {
            if ($calendrier->getClasse() === $this) {
                $calendrier->setClasse(null);
            }
        }
        return $this;
    }

    // Méthodes helper
    public function ajouterEtudiant(User $etudiant): void
    {
        $this->addEtudiant($etudiant);
        $this->effectif = $this->etudiants->count();
    }

    public function consulterEtudiants(): Collection
    {
        return $this->etudiants;
    }
}
