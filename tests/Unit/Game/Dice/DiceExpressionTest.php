<?php

namespace App\Tests\Unit\Game\Dice;

use App\Game\Dice\DiceExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiceExpressionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int, int, int}>
     */
    public static function validNotations(): iterable
    {
        yield '1d20' => ['1d20', 1, 20, 0];
        yield 'd6 (implicit count)' => ['d6', 1, 6, 0];
        yield '3d6+2' => ['3d6+2', 3, 6, 2];
        yield '2d6-1' => ['2d6-1', 2, 6, -1];
        yield 'uppercase D20' => ['D20', 1, 20, 0];
        yield 'padded with whitespace' => ['  1d20  ', 1, 20, 0];
    }

    #[DataProvider('validNotations')]
    public function testParseValidNotation(string $notation, int $count, int $sides, int $modifier): void
    {
        $expression = DiceExpression::parse($notation);

        self::assertSame($count, $expression->count);
        self::assertSame($sides, $expression->sides);
        self::assertSame($modifier, $expression->modifier);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNotations(): iterable
    {
        yield 'empty string' => [''];
        yield 'not dice notation' => ['abc'];
        yield 'zero count' => ['0d6'];
        yield 'zero sides' => ['d0'];
        yield 'garbage after modifier' => ['1d6x2'];
    }

    #[DataProvider('invalidNotations')]
    public function testParseInvalidNotationThrows(string $notation): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DiceExpression::parse($notation);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function roundTrippableNotations(): iterable
    {
        yield '1d20' => ['1d20'];
        yield '3d6+2' => ['3d6+2'];
        yield '2d6-1' => ['2d6-1'];
        yield '1d100' => ['1d100'];
    }

    #[DataProvider('roundTrippableNotations')]
    public function testToStringRoundTrips(string $notation): void
    {
        self::assertSame($notation, (string) DiceExpression::parse($notation));
    }
}
