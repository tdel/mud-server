<?php

namespace App\Network\In\Ingame;

use App\Game\GameWorld;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\CharacterNotFound;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Examine implements ActionInterface
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

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
