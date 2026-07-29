<?php

namespace App\Network\In\Ingame;

use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\CharacterStats;

final class Stats extends AbstractPlayerAction
{
    public function name(): string
    {
        return 'stats';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $player->send(new CharacterStats($player->character()));
    }
}
