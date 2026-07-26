<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class Account implements UserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    private(set) string $login;

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'account')]
    private(set) Collection $characters;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    private(set) ?Character $currentCharacter = null;

    public function __construct(string $login)
    {
        $this->login = $login;
        $this->characters = new ArrayCollection();
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