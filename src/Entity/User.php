<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserEntityInterface extends UserInterface, PasswordAuthenticatedUserInterface
{
    public function getId(): ?int;
    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getFirstname(): ?string;
    public function setFirstname(string $firstname): static;
    public function getLastname(): ?string;
    public function setLastname(string $lastname): static;
    public function getMessagesEnvoyes(): Collection;
    public function getConversations(): Collection;
    public function getNotifications(): Collection;
}

// Classe abstraite pour partager le code commun (mais plus d'entité Doctrine)
abstract class BaseUser implements UserEntityInterface
{
    public function getUserIdentifier(): string
    {
        return (string) $this->getEmail();
    }

    public function getRoles(): array
    {
        $roles = $this->roles ?? [];
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        if ($this->getPassword()) {
            $data["\0".self::class."\0password"] = hash('crc32c', $this->getPassword());
        }
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
