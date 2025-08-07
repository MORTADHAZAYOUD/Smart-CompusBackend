<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection; // Add this line
use Doctrine\Common\Collections\Collection; 
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "role", type: "string")]
#[ORM\DiscriminatorMap(["admin" => Administrator::class, "parent" => ParentUser::class, "teacher" => Teacher::class, "student" => Student::class])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'This email is already in use')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'student:read', 'teacher:read', 'parent:read'])]
    private ?int $id = null;
    

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'student:read', 'teacher:read', 'parent:read'])]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'student:read', 'teacher:read', 'parent:read'])]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'First name must be at least {{ limit }} characters', maxMessage: 'First name cannot be longer than {{ limit }} characters')]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'student:read', 'teacher:read', 'parent:read'])]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Last name must be at least {{ limit }} characters', maxMessage: 'Last name cannot be longer than {{ limit }} characters')]
    private ?string $lastname = null;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
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
    #[ORM\OneToMany(mappedBy: "emetteur", targetEntity: Message::class)]
    private Collection $messagesEnvoyes;

    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: "participants")]
    private Collection $conversations;

    #[ORM\OneToMany(mappedBy: "destinataire", targetEntity: Notification::class)]
    private Collection $notifications;

    public function __construct()
    {
        $this->messagesEnvoyes = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }
}
