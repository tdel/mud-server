<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ItemNotCarried implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("You aren't carrying \"%s\".\n", Ansi::item($this->name)));
    }
}
