<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    /**
     * @var Collection<int, Seance>
     */
    #[ORM\OneToMany(targetEntity: Seance::class, mappedBy: 'enseignant')]
    private Collection $seancesEnseignees;

    /**
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'etudiant')]
    private Collection $presences;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'etudiant')]
    private Collection $notes;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'expediteur')]
    private Collection $messagesEnvoyes;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'destinataire')]
    private Collection $messagesRecus;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants')]
    private Collection $conversations;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'parent')]
    private Collection $enfants;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    private ?self $parent = null;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'enseignant')]
    private Collection $classesEnseignees;

    #[ORM\ManyToOne(inversedBy: 'etudiants')]
    private ?Classe $classe = null;

    public function __construct()
    {
        $this->seancesEnseignees = new ArrayCollection();
        $this->presences = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->messagesEnvoyes = new ArrayCollection();
        $this->messagesRecus = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->enfants = new ArrayCollection();
        $this->classesEnseignees = new ArrayCollection();
        $this->dateCreation = new \DateTime();
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
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return Collection<int, Seance>
     */
    public function getSeancesEnseignees(): Collection
    {
        return $this->seancesEnseignees;
    }

    public function addSeancesEnseignee(Seance $seancesEnseignee): static
    {
        if (!$this->seancesEnseignees->contains($seancesEnseignee)) {
            $this->seancesEnseignees->add($seancesEnseignee);
            $seancesEnseignee->setEnseignant($this);
        }
        return $this;
    }

    public function removeSeancesEnseignee(Seance $seancesEnseignee): static
    {
        if ($this->seancesEnseignees->removeElement($seancesEnseignee)) {
            if ($seancesEnseignee->getEnseignant() === $this) {
                $seancesEnseignee->setEnseignant(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Presence>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(Presence $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setEtudiant($this);
        }
        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            if ($presence->getEtudiant() === $this) {
                $presence->setEtudiant(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setEtudiant($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getEtudiant() === $this) {
                $note->setEtudiant(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessagesEnvoyes(): Collection
    {
        return $this->messagesEnvoyes;
    }

    public function addMessagesEnvoye(Message $messagesEnvoye): static
    {
        if (!$this->messagesEnvoyes->contains($messagesEnvoye)) {
            $this->messagesEnvoyes->add($messagesEnvoye);
            $messagesEnvoye->setExpediteur($this);
        }
        return $this;
    }

    public function removeMessagesEnvoye(Message $messagesEnvoye): static
    {
        if ($this->messagesEnvoyes->removeElement($messagesEnvoye)) {
            if ($messagesEnvoye->getExpediteur() === $this) {
                $messagesEnvoye->setExpediteur(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessagesRecus(): Collection
    {
        return $this->messagesRecus;
    }

    public function addMessagesRecu(Message $messagesRecu): static
    {
        if (!$this->messagesRecus->contains($messagesRecu)) {
            $this->messagesRecus->add($messagesRecu);
            $messagesRecu->setDestinataire($this);
        }
        return $this;
    }

    public function removeMessagesRecu(Message $messagesRecu): static
    {
        if ($this->messagesRecus->removeElement($messagesRecu)) {
            if ($messagesRecu->getDestinataire() === $this) {
                $messagesRecu->setDestinataire(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addParticipant($this);
        }
        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            $conversation->removeParticipant($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(User $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }
        return $this;
    }

    public function removeEnfant(User $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClassesEnseignees(): Collection
    {
        return $this->classesEnseignees;
    }

    public function addClassesEnseignee(Classe $classesEnseignee): static
    {
        if (!$this->classesEnseignees->contains($classesEnseignee)) {
            $this->classesEnseignees->add($classesEnseignee);
            $classesEnseignee->setEnseignant($this);
        }
        return $this;
    }

    public function removeClassesEnseignee(Classe $classesEnseignee): static
    {
        if ($this->classesEnseignees->removeElement($classesEnseignee)) {
            if ($classesEnseignee->getEnseignant() === $this) {
                $classesEnseignee->setEnseignant(null);
            }
        }
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

    // Méthodes helper pour l'authentification et les permissions
    public function authentifier(): bool
    {
        return $this->status === 'active';
    }

    public function modifierProfil(): void
    {
        // Logique pour modifier le profil
    }
}
