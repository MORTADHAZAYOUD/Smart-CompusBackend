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

    // Relation polymorphe avec les utilisateurs
    #[ORM\Column(type: "integer")]
    private ?int $destinataireId = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $destinataireType = null; // 'administrator', 'parent', 'teacher', 'student'

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

    public function getDestinataireId(): ?int
    {
        return $this->destinataireId;
    }

    public function setDestinataireId(?int $destinataireId): static
    {
        $this->destinataireId = $destinataireId;
        return $this;
    }

    public function getDestinataireType(): ?string
    {
        return $this->destinataireType;
    }

    public function setDestinataireType(?string $destinataireType): static
    {
        $this->destinataireType = $destinataireType;
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

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): static
    {
        $this->lu = $lu;
        return $this;
    }

    // Méthode helper pour définir le destinataire
    public function setDestinataire(UserEntityInterface $user): static
    {
        $this->destinataireId = $user->getId();
        
        // Détermine le type basé sur la classe
        switch (get_class($user)) {
            case Administrator::class:
                $this->destinataireType = 'administrator';
                break;
            case ParentUser::class:
                $this->destinataireType = 'parent';
                break;
            case Teacher::class:
                $this->destinataireType = 'teacher';
                break;
            case Student::class:
                $this->destinataireType = 'student';
                break;
        }
        
        return $this;
    }
}
