<?php

namespace App\Network\Out\Connected;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class PasswordMismatch implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write("Passwords didn't match.\n");
    }
}
