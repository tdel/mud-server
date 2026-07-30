<?php

namespace App\Network\Out\Authed;

use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class StoppedPlaying implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $characterName,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("You stop playing %s.\n\n", Ansi::character($this->characterName)));
    }
}
