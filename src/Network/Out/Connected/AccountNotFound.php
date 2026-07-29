<?php

namespace App\Network\Out\Connected;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class AccountNotFound implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $login,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("No account found for \"%s\". Use \"register %s\" to create one.\n", $this->login, $this->login));
    }
}
