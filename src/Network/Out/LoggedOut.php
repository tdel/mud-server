<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class LoggedOut implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write("You have logged out.\nType \"login <name>\" or \"register <name>\" to begin.\n");
    }
}
