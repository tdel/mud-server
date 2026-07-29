<?php

namespace App\Network\In\Ingame;

use App\Game\GameWorld;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\CharacterNotFound;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Usage;

final class Examine extends AbstractPlayerAction
{
    public function __construct(
        private readonly GameWorld $gameWorld,
    ) {
    }

    public function name(): string
    {
        return 'examine';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $player->send(new Usage('examine <name>'));

            return;
        }

        $target = $this->gameWorld->roomInstance($player->currentRoom())->findCharacterByName($name);

        if ($target === null) {
            $player->send(new CharacterNotFound($name));

            return;
        }

        $player->send(new CharacterStats($target));
    }
}
