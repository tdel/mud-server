<?php

namespace App\Entity\Enum;

enum EquipmentSlot: string
{
    case Weapon = 'weapon';
    case Head = 'head';
    case Chest = 'chest';
    case Legs = 'legs';
    case Feet = 'feet';
    case Hands = 'hands';
}
