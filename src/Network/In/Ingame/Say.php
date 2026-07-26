<?php

namespace App\Network\In\Ingame;

use App\Game\GameWorld;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Chat;
use App\Network\Out\SayNothing;
use App\Network\Out\YouSaid;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;

final class Say implements TelnetCommandInterface
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
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $message = trim($argument);

        if ($message === '') {
            $session->player()->send(new SayNothing());

            return;
        }

        $player = $session->player();
        $character = $player->character();

        $this->gameWorld->channelFor($character->currentRoom)->broadcast(
            new Chat($character->name, $message),
            exclude: $player,
        );

        $player->send(new YouSaid($message));
    }
}
