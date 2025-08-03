<?php

namespace App\Entity;

use App\Repository\ParentUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: ParentUserRepository::class)]
class ParentUser extends User
{

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;


    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }
        #[ORM\OneToMany(mappedBy: "parent", targetEntity: Student::class)]
    private Collection $enfants;

    public function __construct()
    {
        parent::__construct();
        $this->enfants = new ArrayCollection();
    }
}
