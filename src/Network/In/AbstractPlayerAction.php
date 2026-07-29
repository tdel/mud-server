<?php

namespace App\Network\In;

use App\Auth\Client;

abstract class AbstractPlayerAction implements ActionInterface
{
    public function onClientAction(Client $client, string $argument): void
    {
        throw new \LogicException(sprintf('%s is a player-only action.', static::class));
    }
}
