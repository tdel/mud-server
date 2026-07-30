<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class Inventory implements OutputTelnetMessageInterface
{
    /**
     * @param string[] $itemNames
     */
    public function __construct(
        private readonly array $itemNames,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write($this->itemNames === []
            ? "You aren't carrying anything.\n"
            : sprintf("You are carrying: %s\n", implode(', ', array_map(Ansi::item(...), $this->itemNames))));
    }
}
