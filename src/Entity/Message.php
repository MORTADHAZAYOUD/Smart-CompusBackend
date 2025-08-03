<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "messagesEnvoyes")]
    private ?User $emetteur = null;

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date;
}