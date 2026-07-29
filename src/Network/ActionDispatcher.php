<?php

namespace App\Network;

use App\Auth\Client;
use App\Network\In\ActionInterface;

final class ActionDispatcher
{
    public function __construct(
        private readonly ActionRegistry $registry,
    ) {
    }

    public function find(ConnectionState $state, string $name): ?ActionInterface
    {
        return $this->registry->find($state, $name);
    }

    public function dispatch(Client $client, ActionInterface $action, string $argument): void
    {
        if ($client->state() === ConnectionState::Ingame) {
            $player = $client->player();
            \assert($player !== null);

            $action->onPlayerAction($player, $argument);
        } else {
            $action->onClientAction($client, $argument);
        }
    }
}
