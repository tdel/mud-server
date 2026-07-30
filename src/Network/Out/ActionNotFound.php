<?php

namespace App\Network\Out;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

class ActionNotFound implements OutputTelnetMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write("Action not found.\n");
    }
}
