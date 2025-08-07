<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection; 
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Student implements UserEntityInterface
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

    // Champs spécifiques à Student
    #[ORM\Column(length: 255)]
    private ?string $numStudent = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\ManyToOne(inversedBy: 'student')]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(targetEntity: ParentUser::class, inversedBy: "enfants")]
    private ?ParentUser $parent = null;

    public function __construct()
    {
        // Plus besoin d'initialiser les collections
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
        $roles[] = 'ROLE_STUDENT';
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

    // Méthodes spécifiques à Student
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
