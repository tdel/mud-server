<?php

namespace App\Network\Telnet;

use App\Network\OutputMessageInterface;

interface OutputTelnetMessageInterface extends OutputMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output): void;
}
