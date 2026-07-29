<?php

namespace App\Network\In;

use App\Game\Player;

abstract class AbstractClientAction implements ActionInterface
{
    public function onPlayerAction(Player $player, string $argument): void
    {
        throw new \LogicException(sprintf('%s is a client-only action.', static::class));
    }
}
