<?php

namespace App\Network\Out\Connected;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class LoginAlreadyTaken implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $login,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("The login \"%s\" is already taken.\n", $this->login));
    }
}
