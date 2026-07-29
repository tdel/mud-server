<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class RoomExit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'exits')]
    private(set) Room $sourceRoom;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    private(set) Room $targetRoom;

    #[ORM\Column]
    private(set) string $direction; // "nord", "sud", etc.
}
