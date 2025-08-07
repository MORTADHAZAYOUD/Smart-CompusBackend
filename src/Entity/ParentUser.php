<?php

namespace App\Entity;

use App\Repository\ParentUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ParentUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class ParentUser implements UserEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    // Note: Les relations avec Message, Conversation et Notification sont gérées via des relations polymorphes

    // Champs spécifiques à ParentUser
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: "parent", targetEntity: Student::class)]
    private Collection $enfants;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        $roles[] = 'ROLE_PARENT';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getMessagesEnvoyes(): Collection
    {
        // Retourne une collection vide - les messages doivent être récupérés via query polymorphe
        return new ArrayCollection();
    }

    public function getConversations(): Collection
    {
        // Retourne une collection vide - les conversations doivent être récupérées via query polymorphe
        return new ArrayCollection();
    }

    public function getNotifications(): Collection
    {
        // Retourne une collection vide - les notifications doivent être récupérées via query polymorphe
        return new ArrayCollection();
    }

    // Méthodes spécifiques à ParentUser
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

    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(Student $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }
        return $this;
    }

    public function removeEnfant(Student $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }
        return $this;
    }
}
