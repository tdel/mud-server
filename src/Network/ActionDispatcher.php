<?php

namespace App\Network;

use App\Network\In\ActionInterface;
use App\Network\Out\ActionNotFound;

final class ActionDispatcher
{
    public function __construct(
        private readonly ActionRegistry $registry,
    ) {
    }

    public function dispatch(UserInterface $user, string $actionName, string $argument): void
    {
        $action = $this->registry->find($user->state(), $actionName);
        if (null === $action) {
            $user->send(new ActionNotFound());

            return;
        }

        // log before
        $action->onReceive($user, $argument);

        // maybe log after ?

        /*
        if ($user->state() === ConnectionState::Ingame) {
            $player = $client->player();
            \assert($player !== null);

            $action->onPlayerAction($player, $argument);
        } else {
            $action->onClientAction($client, $argument);
        }*/
    }
}
