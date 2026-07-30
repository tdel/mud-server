<?php

namespace App\Network\Out\Connected;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class AccountAlreadyConnected implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $login,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("The account \"%s\" is already connected.\n", $this->login));
    }
}
