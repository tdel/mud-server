<?php

namespace App\Network\Out\Connected;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class Goodbye implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write("Goodbye!\n");
    }
}
