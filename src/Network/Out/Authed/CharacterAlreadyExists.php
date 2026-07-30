<?php

namespace App\Network\Out\Authed;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterAlreadyExists implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("You already have a character named \"%s\".\n", Ansi::character($this->name)));
    }
}
