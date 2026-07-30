<?php

namespace App\Tests\Unit\Game\Dice;

use App\Game\Dice\DiceRoll;
use PHPUnit\Framework\TestCase;

final class DiceRollTest extends TestCase
{
    public function testTotalSumsRollsAndModifier(): void
    {
        $roll = new DiceRoll([4, 6, 2], 3);

        self::assertSame(15, $roll->total());
    }

    public function testTotalWithNegativeModifier(): void
    {
        $roll = new DiceRoll([5], -2);

        self::assertSame(3, $roll->total());
    }

    public function testTotalWithNoRolls(): void
    {
        $roll = new DiceRoll([], 7);

        self::assertSame(7, $roll->total());
    }
}
