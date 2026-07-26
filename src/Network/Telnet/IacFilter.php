<?php

namespace App\Network\Telnet;

/**
 * Strips telnet IAC (Interpret As Command) sequences that clients send
 * unprompted (e.g. terminal-type/echo negotiation). No option negotiation is
 * performed server-side; this only keeps those bytes from polluting the
 * line-based game input.
 */
final class IacFilter
{
    public static function strip(string $data): string
    {
        $data = preg_replace('/\xFF\xFA[\s\S]*?\xFF\xF0/', '', $data) ?? $data;
        $data = preg_replace('/\xFF[\xFB-\xFE]./s', '', $data) ?? $data;
        $data = preg_replace('/\xFF[^\xFF]/s', '', $data) ?? $data;

        return str_replace("\xFF\xFF", "\xFF", $data);
    }
}
