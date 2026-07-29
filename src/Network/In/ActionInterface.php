<?php

namespace App\Network\In;

use App\Auth\Client;
use App\Game\Player;
use App\Network\ConnectionState;

interface ActionInterface
{
    /**
     * The command word a player types to trigger this action (e.g. "look").
     */
    public function name(): string;

    /**
     * @return list<ConnectionState> the states this action is usable in
     */
    public function states(): array;

    public function onClientAction(Client $client, string $argument): void;

    public function onPlayerAction(Player $player, string $argument): void;
}
