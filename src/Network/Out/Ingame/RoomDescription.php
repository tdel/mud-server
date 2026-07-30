<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\Ansi;
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

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf(
            "== %s ==\n%s\n\nExits: %s\nCharacters here: %s\nItems: %s\n",
            Ansi::room($this->roomName),
            $this->description,
            $this->exitNames === [] ? 'none.' : implode(', ', array_map(Ansi::direction(...), $this->exitNames)),
            $this->characterNames === []
                ? 'no one else.'
                : implode(', ', array_map(Ansi::character(...), $this->characterNames)),
            $this->itemNames === [] ? 'none.' : implode(', ', array_map(Ansi::item(...), $this->itemNames)),
        ));
    }
}
