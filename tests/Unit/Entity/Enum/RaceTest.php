<?php

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\Race;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RaceTest extends TestCase
{
    /**
     * @return iterable<string, array{Race, array<string, int>, string}>
     */
    public static function races(): iterable
    {
        yield 'Dwarf' => [
            Race::Dwarf,
            ['strength' => 2, 'constitution' => 2],
            'Dwarf',
        ];
        yield 'Human' => [
            Race::Human,
            [
                'strength' => 1,
                'dexterity' => 1,
                'constitution' => 1,
                'intelligence' => 1,
                'wisdom' => 1,
                'charisma' => 1,
            ],
            'Human',
        ];
        yield 'High Elf' => [
            Race::HighElf,
            ['dexterity' => 2, 'intelligence' => 2, 'wisdom' => 1, 'charisma' => 1],
            'High Elf',
        ];
        yield 'Orc' => [
            Race::Orc,
            ['strength' => 2, 'dexterity' => 1, 'wisdom' => 1],
            'Orc',
        ];
    }

    /**
     * @param array<string, int> $expectedBonuses
     */
    #[DataProvider('races')]
    public function testAbilityScoreBonuses(Race $race, array $expectedBonuses, string $expectedLabel): void
    {
        self::assertSame($expectedBonuses, $race->abilityScoreBonuses());
        self::assertSame($expectedLabel, $race->label());
    }

    public function testEveryCaseIsCovered(): void
    {
        self::assertCount(4, Race::cases());
    }
}
