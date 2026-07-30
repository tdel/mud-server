<?php

namespace App\Network\Out\Ingame;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class YouSaid implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly string $text,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        $output->write(sprintf("You say: %s\n", $this->text));
    }
}
