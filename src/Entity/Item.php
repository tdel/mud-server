<?php

namespace App\Entity;

use App\Entity\Enum\EquipmentSlot;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\ManyToOne(targetEntity: ItemTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private(set) ItemTemplate $template;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'items')]
    private(set) ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'items')]
    private(set) ?Character $character = null;

    #[ORM\Column(length: 20, enumType: EquipmentSlot::class, nullable: true)]
    private(set) ?EquipmentSlot $slot = null;

    public function __construct(ItemTemplate $template)
    {
        $this->template = $template;
    }

    public function moveToRoom(Room $room): void
    {
        $this->room = $room;
        $this->character = null;
        $this->slot = null;
    }

    public function moveToCharacter(Character $character): void
    {
        $this->character = $character;
        $this->room = null;
        $this->slot = null;
    }

    public function equip(EquipmentSlot $slot): void
    {
        if ($this->character === null) {
            throw new \LogicException('Item must be carried by a character before it can be equipped.');
        }

        if ($this->template->type->equipmentSlot() !== $slot) {
            throw new \LogicException(sprintf('Item type "%s" cannot be equipped in slot "%s".', $this->template->type->value, $slot->value));
        }

        $this->slot = $slot;
    }

    public function unequip(): void
    {
        $this->slot = null;
    }
}
