<?php

namespace App\Game\Dice;

final class DiceRoll
{
    /**
     * @param int[] $rolls result of each individual die, before the modifier
     */
    public function __construct(
        public readonly array $rolls,
        public readonly int $modifier,
    ) {
    }

    public function total(): int
    {
        return array_sum($this->rolls) + $this->modifier;
    }
}
