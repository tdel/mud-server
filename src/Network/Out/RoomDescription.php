<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class RoomDescription implements OutputTelnetMessageInterface
{
    /**
     * @param string[] $exitNames
     * @param string[] $characterNames
     * @param string[] $itemNames
     */
    public function __construct(
        private readonly string $roomName,
        private readonly string $description,
        private readonly array $exitNames,
        private readonly array $characterNames,
        private readonly array $itemNames,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write(sprintf(
            "== %s ==\n%s\n\nExits: %s\nCharacters here: %s\nItems: %s\n",
            $this->roomName,
            $this->description,
            $this->exitNames === [] ? 'none.' : implode(', ', $this->exitNames),
            $this->characterNames === [] ? 'no one else.' : implode(', ', $this->characterNames),
            $this->itemNames === [] ? 'none.' : implode(', ', $this->itemNames),
        ));
    }
}
