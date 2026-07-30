<?php

namespace App\Network\In\Ingame;

use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\UserInterface;

final class Stats implements ActionInterface
{
    public function name(): string
    {
        return 'stats';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

        $player->send(new CharacterStats($player->character()));
    }
}
