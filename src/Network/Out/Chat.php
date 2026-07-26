<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class Chat implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $speakerName,
        private readonly string $text,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("%s says: %s\n", $this->speakerName, $this->text));
    }
}
