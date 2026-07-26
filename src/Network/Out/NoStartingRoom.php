<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class NoStartingRoom implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write("No starting room is configured. Contact the administrator (make console app:room:create).\n");
    }
}
