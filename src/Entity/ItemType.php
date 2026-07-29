<?php

namespace App\Entity;

use App\Entity\Enum\EquipmentSlot;

enum ItemType: string
{
    case Weapon = 'weapon';
    case Helmet = 'helmet';
    case Armor = 'armor';
    case Pants = 'pants';
    case Boots = 'boots';
    case Gloves = 'gloves';
    case Potion = 'potion';
    case Key = 'key';
    case Tool = 'tool';
    case Misc = 'misc';

    public function equipmentSlot(): ?EquipmentSlot
    {
        return match ($this) {
            self::Weapon => EquipmentSlot::Weapon,
            self::Helmet => EquipmentSlot::Head,
            self::Armor => EquipmentSlot::Chest,
            self::Pants => EquipmentSlot::Legs,
            self::Boots => EquipmentSlot::Feet,
            self::Gloves => EquipmentSlot::Hands,
            default => null,
        };
    }
}
