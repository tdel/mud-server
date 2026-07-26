<?php

namespace App\Network\Out;

use App\Auth\Client;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class CharacterList implements OutputTelnetMessageInterface
{
    /**
     * @param string[] $names
     */
    public function __construct(
        private readonly array $names,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $output->write($this->names === []
            ? "You have no characters yet. Use \"character-create <name>\" to make one.\n"
            : sprintf("Characters: %s\n", implode(', ', $this->names)));
        $output->write("Commands: character-select <name>, character-create <name>, character-delete <name>, logout\n");
    }
}
