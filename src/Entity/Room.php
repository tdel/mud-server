<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_room_starting', columns: ['is_starting_room'])]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    private(set) string $name;

    #[ORM\Column(type: 'text')]
    private(set) string $description;

    #[ORM\Column(nullable: true)]
    private(set) ?bool $isStartingRoom = null;

    #[ORM\OneToMany(targetEntity: RoomExit::class, mappedBy: 'sourceRoom')]
    private(set) Collection $exits;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'room')]
    private(set) Collection $items;

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'currentRoom')]
    private(set) Collection $characters;

    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->exits = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->characters = new ArrayCollection();
    }

    public function markAsStartingRoom(): void
    {
        $this->isStartingRoom = true;
    }

    public function unmarkAsStartingRoom(): void
    {
        $this->isStartingRoom = null;
    }
}
