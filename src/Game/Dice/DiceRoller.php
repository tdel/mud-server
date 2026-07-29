<?php

namespace App\Game\Dice;

final class DiceRoller
{
    private const SIMULATED_SIDES = [2, 3];

    public function roll(DiceExpression|string $expression): DiceRoll
    {
        $expression = is_string($expression) ? DiceExpression::parse($expression) : $expression;

        $rolls = [];
        for ($i = 0; $i < $expression->count; $i++) {
            $rolls[] = $this->rollDie($expression->sides);
        }

        return new DiceRoll($rolls, $expression->modifier);
    }

    private function rollDie(int $sides): int
    {
        if ($sides === 100) {
            return $this->rollPercentile();
        }

        if (in_array($sides, self::SIMULATED_SIDES, true)) {
            // d2/d3 don't exist physically: roll a die with double the sides
            // and halve the result, rounded up.
            return (int) ceil(random_int(1, $sides * 2) / 2);
        }

        return random_int(1, $sides);
    }

    private function rollPercentile(): int
    {
        // 2d10: one for the tens digit (0-9), one for the units (0-9) - not
        // a sum. Double 0 is 100, there is no 0 result on a d100.
        $tens = random_int(0, 9);
        $units = random_int(0, 9);
        $result = $tens * 10 + $units;

        return $result === 0 ? 100 : $result;
    }
}
