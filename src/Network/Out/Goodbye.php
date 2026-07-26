<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class Goodbye implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write("Goodbye!\n");
    }
}
