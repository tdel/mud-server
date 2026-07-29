<?php

namespace App\Entity\Enum;

enum Race: string
{
    case Dwarf = 'dwarf';
    case Human = 'human';
    case HighElf = 'high_elf';
    case Orc = 'orc';

    public function label(): string
    {
        return match ($this) {
            self::Dwarf => 'Dwarf',
            self::Human => 'Human',
            self::HighElf => 'High Elf',
            self::Orc => 'Orc',
        };
    }

    /**
     * @return array<string, int> bonus keyed by Character ability property name
     */
    public function abilityScoreBonuses(): array
    {
        return match ($this) {
            self::Dwarf => ['strength' => 2, 'constitution' => 2],
            self::Human => ['strength' => 1, 'dexterity' => 1, 'constitution' => 1, 'intelligence' => 1, 'wisdom' => 1, 'charisma' => 1],
            self::HighElf => ['dexterity' => 2, 'intelligence' => 2, 'wisdom' => 1, 'charisma' => 1],
            self::Orc => ['strength' => 2, 'dexterity' => 1, 'wisdom' => 1],
        };
    }
}
