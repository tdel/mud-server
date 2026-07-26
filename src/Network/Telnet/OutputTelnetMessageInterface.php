<?php

namespace App\Network\Telnet;

use App\Auth\Client;
use App\Network\OutputMessageInterface;

interface OutputTelnetMessageInterface extends OutputMessageInterface
{
    public function toTelnet(TelnetOutputInterface $output, Client $client): void;
}
