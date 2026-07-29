<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\Ansi;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterJoinedRoom implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $characterName,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf("%s vous a rejoint.\n", Ansi::character($this->characterName)));
    }
}
