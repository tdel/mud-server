<?php

namespace App\Network\Out\Connected;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class WelcomeBack implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $login,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("Welcome back, %s!\n\n", $this->login));
    }
}
