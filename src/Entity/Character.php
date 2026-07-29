<?php

namespace App\Entity;

use App\Entity\Enum\EquipmentSlot;
use App\Entity\Enum\Race;
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

    #[ORM\Column(length: 20, enumType: Race::class)]
    private(set) Race $race;

    #[ORM\Column]
    private(set) int $currentHealth;

    #[ORM\Column]
    private(set) int $maxHealth;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $currentMana;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $maxMana;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $strength;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $dexterity;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $constitution;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $intelligence;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $wisdom;

    #[ORM\Column(options: ['default' => 10])]
    private(set) int $charisma;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'character')]
    private(set) Collection $items;

    public function __construct(
        Account $account,
        Room $currentRoom,
        string $name,
        Race $race,
        int $maxHealth,
        int $maxMana = 10,
        int $strength = 10,
        int $dexterity = 10,
        int $constitution = 10,
        int $intelligence = 10,
        int $wisdom = 10,
        int $charisma = 10,
    ) {
        $this->account = $account;
        $this->currentRoom = $currentRoom;
        $this->name = $name;
        $this->race = $race;
        $this->maxHealth = $maxHealth;
        $this->currentHealth = $maxHealth;
        $this->maxMana = $maxMana;
        $this->currentMana = $maxMana;
        $this->strength = $strength;
        $this->dexterity = $dexterity;
        $this->constitution = $constitution;
        $this->intelligence = $intelligence;
        $this->wisdom = $wisdom;
        $this->charisma = $charisma;
        $this->items = new ArrayCollection();
    }

    public function moveToRoom(Room $room): void
    {
        $this->currentRoom = $room;
    }

    public function equip(Item $item, EquipmentSlot $slot): void
    {
        if ($item->character !== $this) {
            throw new \LogicException('Item is not in this character\'s inventory.');
        }

        $item->equip($slot);
    }
}
