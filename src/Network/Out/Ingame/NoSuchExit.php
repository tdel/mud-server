<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class NoSuchExit implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $direction,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("No exit to the \"%s\".\n", Ansi::direction($this->direction)));
    }
}
