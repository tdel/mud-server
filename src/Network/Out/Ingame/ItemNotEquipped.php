<?php

namespace App\Network\Out\Ingame;

use App\Auth\Client;
use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ItemNotEquipped implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("You aren't wearing or wielding the %s.\n", Ansi::item($this->name)));
    }
}
