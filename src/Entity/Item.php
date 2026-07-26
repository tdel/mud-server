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

    #[ORM\Column(length: 255)]
    private(set) string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private(set) ?string $description = null;

    #[ORM\Column(length: 50)]
    private(set) string $type; // "key", "tool", "weapon", "potion", etc.

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'items')]
    private(set) ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'items')]
    private(set) ?Character $character = null;

}