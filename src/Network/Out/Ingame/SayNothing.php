<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class SayNothing implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write("Say what?\n");
    }
}
