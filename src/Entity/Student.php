<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection; // Add this line
use Doctrine\Common\Collections\Collection; 
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student extends User
{


    #[ORM\Column(length: 255)]
    #[Groups(['student:read', 'classe:read'])]
    #[Assert\NotBlank(message: 'Student number is required')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Student number must be at least {{ limit }} characters', maxMessage: 'Student number cannot be longer than {{ limit }} characters')]
    private ?string $numStudent = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['student:read'])]
    #[Assert\NotNull(message: 'Birth date is required')]
    #[Assert\Type(\DateTime::class, message: 'Birth date must be a valid date')]
    private ?\DateTime $dateNaissance = null;

    #[ORM\ManyToOne(inversedBy: 'student')]
    #[Groups(['student:read'])]
    private ?Classe $classe = null;
    
    #[ORM\ManyToOne(targetEntity: ParentUser::class, inversedBy: "enfants")]
    #[Groups(['student:read'])]
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
}
