<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['classe:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['classe:read'])]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'classe')]
    #[Groups(['classe:read'])]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: "classe", targetEntity: Seance::class)]
    private Collection $seances;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->seances = new ArrayCollection();
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

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setClasse($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            if ($student->getClasse() === $this) {
                $student->setClasse(null);
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
}
