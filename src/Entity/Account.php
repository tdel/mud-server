<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class Account implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    private(set) string $login;

    #[ORM\Column(length: 255)]
    private(set) string $password;

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'account')]
    private(set) Collection $characters;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    private(set) ?Character $currentCharacter = null;

    public function __construct(string $login)
    {
        $this->login = $login;
        $this->characters = new ArrayCollection();
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setCurrentCharacter(Character $character): void
    {
        $this->currentCharacter = $character;
    }

    public function clearCurrentCharacter(): void
    {
        $this->currentCharacter = null;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->id->toString();
    }
}
