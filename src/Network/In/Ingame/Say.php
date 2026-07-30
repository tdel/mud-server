<?php

namespace App\Network\In\Ingame;

use App\Game\GameWorld;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\Chat;
use App\Network\Out\Ingame\SayNothing;
use App\Network\Out\Ingame\YouSaid;
use App\Network\UserInterface;

final class Say implements ActionInterface
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

        $message = trim($argument);

        if ($message === '') {
            $player->send(new SayNothing());

            return;
        }

        $character = $player->character();

        $this->gameWorld->roomInstance($character->currentRoom)->broadcast(
            new Chat($character->name, $message),
            exclude: $player,
        );

        $player->send(new YouSaid($message));
    }
}
