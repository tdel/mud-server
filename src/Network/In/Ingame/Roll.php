<?php

namespace App\Network\In\Ingame;

use App\Game\Dice\DiceExpression;
use App\Game\Dice\DiceRoller;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\DiceRolled;
use App\Network\Out\Usage;

final class Roll extends AbstractPlayerAction
{
    public function __construct(
        private readonly DiceRoller $diceRoller,
    ) {
    }

    public function name(): string
    {
        return 'roll';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $notation = trim($argument);

        if ($notation === '') {
            $player->send(new Usage('roll <XdY+Z>'));

            return;
        }

        try {
            $expression = DiceExpression::parse($notation);
        } catch (\InvalidArgumentException) {
            $player->send(new Usage('roll <XdY+Z>'));

            return;
        }

        $player->send(new DiceRolled($expression, $this->diceRoller->roll($expression)));
    }
}
