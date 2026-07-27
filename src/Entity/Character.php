<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'character')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'characters')]
    private(set) Account $account;

    #[ORM\Column(length: 255)]
    private(set) string $name;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'characters')]
    private(set) Room $currentRoom;

    #[ORM\Column]
    private(set) int $health;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'character')]
    private(set) Collection $items;

    public function __construct(Account $account, Room $currentRoom, string $name, int $health)
    {
        $this->account = $account;
        $this->currentRoom = $currentRoom;
        $this->name = $name;
        $this->health = $health;
        $this->items = new ArrayCollection();
    }

    public function moveToRoom(Room $room): void
    {
        $this->currentRoom = $room;
    }
}