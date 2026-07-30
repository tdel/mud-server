<?php

namespace App\Network\In;

use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\UserInterface;

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

    public function onReceive(UserInterface $user, string $argument): void;

}
