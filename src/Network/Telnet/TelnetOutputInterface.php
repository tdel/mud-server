<?php

namespace App\Network\Telnet;

/**
 * The narrow view of a telnet session that Message classes are allowed to
 * see — just writing text out. Everything else on TelnetSession (state,
 * account, player, world membership...) stays invisible to Views.
 */
interface TelnetOutputInterface
{
    public function write(string $text): void;
}
