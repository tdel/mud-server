<?php

namespace App\Entity;

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

    public function __construct(ItemTemplate $template)
    {
        $this->template = $template;
    }

    public function moveToRoom(Room $room): void
    {
        $this->room = $room;
        $this->character = null;
    }

    public function moveToCharacter(Character $character): void
    {
        $this->character = $character;
        $this->room = null;
    }
}
