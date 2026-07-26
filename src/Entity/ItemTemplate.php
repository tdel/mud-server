<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\ItemTemplateRepository::class)]
class ItemTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private(set) Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    private(set) string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private(set) ?string $description = null;

    #[ORM\Column(length: 50, enumType: ItemType::class)]
    private(set) ItemType $type;

    #[ORM\Column]
    private(set) int $weight;

    public function __construct(Uuid $id, string $name, ?string $description, ItemType $type, int $weight)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->weight = $weight;
    }
}
