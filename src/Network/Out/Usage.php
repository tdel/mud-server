<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class Usage implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $usage,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("Usage: %s\n", $this->usage));
    }
}
