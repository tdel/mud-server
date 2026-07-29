<?php

namespace App\Network\Out\Connected;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class AccountCreated implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $login,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("Account \"%s\" created.\n\n", $this->login));
    }
}
