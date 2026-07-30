<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ItemNotFound implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("There is no \"%s\" here.\n", Ansi::item($this->name)));
    }
}
