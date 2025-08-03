<?php

namespace App\Entity;

use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: TeacherRepository::class)]
class Teacher extends User
{


    #[ORM\Column(length: 255)]
    private ?string $specialite = null;

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }
    #[ORM\OneToMany(mappedBy: "teacher", targetEntity: Seance::class)]
    private Collection $seances;

    public function __construct()
    {
        parent::__construct();
        $this->seances = new ArrayCollection();
    }

    public function getSeances(): Collection
    {
        return $this->seances;
    }
}
