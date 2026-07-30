<?php

namespace App\Network\Out\Authed;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class InvalidRace implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $input,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("\"%s\" is not a valid race.\n", $this->input));
    }
}
