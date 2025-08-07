<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection; // Add this line
use Doctrine\Common\Collections\Collection; 
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student extends User
{


    #[ORM\Column(length: 255)]
    private ?string $numStudent = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\ManyToOne(inversedBy: 'student')]
    private ?Classe $classe = null;
    #[ORM\ManyToOne(targetEntity: ParentUser::class, inversedBy: "enfants")]
    private ?ParentUser $parent = null;



    public function getNumStudent(): ?string
    {
        return $this->numStudent;
    }

    public function setNumStudent(string $numStudent): static
    {
        $this->numStudent = $numStudent;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

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

    public function getParent(): ?ParentUser
    {
        return $this->parent;
    }

    public function setParent(?ParentUser $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
