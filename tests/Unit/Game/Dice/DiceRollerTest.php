<?php

namespace App\Tests\Unit\Game\Dice;

use App\Game\Dice\DiceRoller;
use PHPUnit\Framework\TestCase;

final class DiceRollerTest extends TestCase
{
    private const ITERATIONS = 200;

    public function testPercentileRollIsAlwaysWithinBounds(): void
    {
        $roller = new DiceRoller();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $roll = $roller->roll('1d100');

            self::assertCount(1, $roll->rolls);
            self::assertGreaterThanOrEqual(1, $roll->rolls[0]);
            self::assertLessThanOrEqual(100, $roll->rolls[0]);
        }
    }

    public function testSimulatedD2IsAlwaysWithinBounds(): void
    {
        $roller = new DiceRoller();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $roll = $roller->roll('1d2');

            self::assertGreaterThanOrEqual(1, $roll->rolls[0]);
            self::assertLessThanOrEqual(2, $roll->rolls[0]);
        }
    }

    public function testSimulatedD3IsAlwaysWithinBounds(): void
    {
        $roller = new DiceRoller();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $roll = $roller->roll('1d3');

            self::assertGreaterThanOrEqual(1, $roll->rolls[0]);
            self::assertLessThanOrEqual(3, $roll->rolls[0]);
        }
    }

    public function testOrdinaryDieIsAlwaysWithinBounds(): void
    {
        $roller = new DiceRoller();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $roll = $roller->roll('4d6');

            self::assertCount(4, $roll->rolls);
            foreach ($roll->rolls as $die) {
                self::assertGreaterThanOrEqual(1, $die);
                self::assertLessThanOrEqual(6, $die);
            }
        }
    }

    public function testModifierIsAppliedToTotal(): void
    {
        $roller = new DiceRoller();

        $roll = $roller->roll('1d6+100');

        self::assertSame(array_sum($roll->rolls) + 100, $roll->total());
    }

    public function testInvalidNotationThrows(): void
    {
        $roller = new DiceRoller();

        $this->expectException(\InvalidArgumentException::class);

        $roller->roll('not-a-dice-notation');
    }
}
