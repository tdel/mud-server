<?php

namespace App\Network\In\Ingame;

use App\Game\GameWorld;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\Chat;
use App\Network\Out\Ingame\SayNothing;
use App\Network\Out\Ingame\YouSaid;

final class Say extends AbstractPlayerAction
{
    public function __construct(
        private readonly GameWorld $gameWorld,
    ) {
    }

    public function name(): string
    {
        return 'say';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $message = trim($argument);

        if ($message === '') {
            $player->send(new SayNothing());

            return;
        }

        $character = $player->character();

        $this->gameWorld->channelFor($character->currentRoom)->broadcast(
            new Chat($character->name, $message),
            exclude: $player,
        );

        $player->send(new YouSaid($message));
    }
}
