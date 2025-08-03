<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "notifications")]
    private ?User $destinataire = null;

    #[ORM\Column(type: "string")]
    private string $contenu;

    #[ORM\Column(type: "boolean")]
    private bool $vue;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateCreation;
    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "string", length: 20)]
    private string $priorite = 'normale';

    #[ORM\Column(type: "boolean")]
    private bool $lu = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getDestinataire(): ?User
    {
        return $this->destinataire;
    }
    public function setDestinataire(?User $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
    }
    public function getContenu(): string
    {
        return $this->contenu;
    }
    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }
    public function isVue(): bool
    {
        return $this->vue;
    }
    public function setVue(bool $vue): static
    {
        $this->vue = $vue;

        return $this;
    }
    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }
    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }
    public function getTitre(): string
    {
        return $this->titre;
    }
    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }
    public function getPriorite(): string
    {
        return $this->priorite;
    }
    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

}
