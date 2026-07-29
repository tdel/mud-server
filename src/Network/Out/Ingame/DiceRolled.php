<?php

namespace App\Network\Out\Ingame;

use App\Auth\Client;
use App\Game\Dice\DiceExpression;
use App\Game\Dice\DiceRoll;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetOutputInterface;

final class DiceRolled implements OutputTelnetMessageInterface
{
    public function __construct(
        private readonly DiceExpression $expression,
        private readonly DiceRoll $result,
    ) {
    }

    public function toTelnet(TelnetOutputInterface $output, Client $client): void
    {
        $modifier = match (true) {
            $this->result->modifier > 0 => sprintf(' + %d', $this->result->modifier),
            $this->result->modifier < 0 => sprintf(' - %d', abs($this->result->modifier)),
            default => '',
        };

        $output->write(sprintf(
            "You roll %s: [%s]%s = %d\n",
            (string) $this->expression,
            implode(', ', $this->result->rolls),
            $modifier,
            $this->result->total(),
        ));
    }
}
