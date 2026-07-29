<?php

namespace App\Network\Out\Ingame;

use App\Auth\Client;
use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterLeftRoom implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $characterName,
        private readonly string $targetRoomName,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf(
            "%s est parti vers %s.\n",
            Ansi::character($this->characterName),
            Ansi::room($this->targetRoomName),
        ));
    }
}
