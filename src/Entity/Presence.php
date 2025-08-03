<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class)]
    private ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Seance::class)]
    private ?Seance $seance = null;

    #[ORM\Column(type: "boolean")]
    private bool $present;
}