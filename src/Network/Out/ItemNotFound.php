<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class ItemNotFound implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("There is no \"%s\" here.\n", $this->name));
    }
}
