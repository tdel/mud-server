<?php

namespace App\Entity;

enum ItemType: string
{
    case Weapon = 'weapon';
    case Potion = 'potion';
    case Key = 'key';
    case Tool = 'tool';
    case Misc = 'misc';
}
