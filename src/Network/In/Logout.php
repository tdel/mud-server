<?php

namespace App\Network\In;

use App\Auth\AuthWorld;
use App\Auth\Client;
use App\Game\GameWorld;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\Out\LoggedOut;

/**
 * Usable from both the "authed" and "ingame" states: logging out always
 * returns the client directly to "connected".
 */
final class Logout implements ActionInterface
{
    public function __construct(
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
    ) {
    }

    public function name(): string
    {
        return 'logout';
    }

    public function states(): array
    {
        return [ConnectionState::Authed, ConnectionState::Ingame];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $this->finishLogout($client);
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $this->gameWorld->exitWorld($player);
        $this->finishLogout($player->client());
    }

    private function finishLogout(Client $client): void
    {
        $client->setAccount(null);
        $client->setPlayer(null);
        $this->authWorld->enterWorld($client);
        $client->setState(ConnectionState::Connected);

        $client->send(new LoggedOut());
    }
}
