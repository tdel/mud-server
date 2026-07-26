<?php

namespace App\Network\In;

use App\Auth\AuthWorld;
use App\Game\GameWorld;
use App\Network\Out\LoggedOut;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;

/**
 * Usable from both the "authed" and "ingame" states: logging out always
 * returns the session directly to "connected".
 */
final class Logout implements TelnetCommandInterface
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
        return [TelnetState::Authed, TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        if ($session->state() === TelnetState::Ingame) {
            $this->gameWorld->exitWorld($session->player());
        }

        $session->setAccount(null);
        $session->setPlayer(null);
        $this->authWorld->enterWorld($session->client());
        $session->setState(TelnetState::Connected);

        $session->client()->send(new LoggedOut());
    }
}
