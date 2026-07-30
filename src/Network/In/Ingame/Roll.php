<?php

namespace App\Network\In\Ingame;

use App\Game\Dice\DiceExpression;
use App\Game\Dice\DiceRoller;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\DiceRolled;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Roll implements ActionInterface
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

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
