<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection; // Add this line
use Doctrine\Common\Collections\Collection; 
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
class Administrator extends User
{
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $privileges = null;

    public function getPrivileges(): ?array
    {
        return $this->privileges;
    }

    public function setPrivileges(?array $privileges): static
    {
        $this->privileges = $privileges;

        return $this;
    }
}