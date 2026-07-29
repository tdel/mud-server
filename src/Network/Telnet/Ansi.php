<?php

namespace App\Network\Telnet;

final class Ansi
{
    private const RESET = "\x1b[0m";

    public static function room(string $text): string
    {
        return self::wrap($text, '1;36');
    }

    public static function direction(string $text): string
    {
        return self::wrap($text, '33');
    }

    public static function item(string $text): string
    {
        return self::wrap($text, '32');
    }

    public static function character(string $text): string
    {
        return self::wrap($text, '35');
    }

    private static function wrap(string $text, string $sgrCode): string
    {
        return "\x1b[{$sgrCode}m{$text}" . self::RESET;
    }
}
