<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterDisconnected implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $characterName,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("%s s'est déconnecté.\n", Ansi::character($this->characterName)));
    }
}
