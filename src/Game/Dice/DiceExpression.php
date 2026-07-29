<?php

namespace App\Game\Dice;

final class DiceExpression
{
    private function __construct(
        public readonly int $count,
        public readonly int $sides,
        public readonly int $modifier,
    ) {
    }

    public static function parse(string $notation): self
    {
        $notation = strtolower(trim($notation));

        if (!preg_match('/^(\d*)d(\d+)([+-]\d+)?$/', $notation, $matches)) {
            throw new \InvalidArgumentException(sprintf('Invalid dice notation: "%s".', $notation));
        }

        $count = $matches[1] === '' ? 1 : (int) $matches[1];
        $sides = (int) $matches[2];

        if ($count < 1 || $sides < 1) {
            throw new \InvalidArgumentException(sprintf('Invalid dice notation: "%s".', $notation));
        }

        return new self($count, $sides, isset($matches[3]) ? (int) $matches[3] : 0);
    }

    public function __toString(): string
    {
        $modifier = match (true) {
            $this->modifier > 0 => sprintf('+%d', $this->modifier),
            $this->modifier < 0 => (string) $this->modifier,
            default => '',
        };

        return sprintf('%dd%d%s', $this->count, $this->sides, $modifier);
    }
}
