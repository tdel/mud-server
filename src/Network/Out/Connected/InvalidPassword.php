<?php

namespace App\Network\Out\Connected;

use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class InvalidPassword implements OutputTelnetMessageInterface
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        private readonly array $reasons,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output): void
    {
        foreach ($this->reasons as $reason) {
            $output->write(sprintf("%s\n", $reason));
        }
    }
}
