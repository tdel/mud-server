<?php

namespace App\Network\In;

use App\Game\AuthWorld;
use App\Game\GameWorld;
use App\Network\ConnectionState;
use App\Network\In\Authed\CharacterList;
use App\Network\Out\Authed\StoppedPlaying;
use App\Network\Out\LoggedOut;
use App\Network\UserInterface;

/**
 * Usable from both the "authed" and "ingame" states. From "ingame", logging
 * out only drops the character and returns to character selection ("authed").
 * From "authed", logging out returns all the way to "connected".
 */
final class Logout implements ActionInterface
{
    public function __construct(
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly CharacterList $characterListAction,
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

    public function onReceive(UserInterface $user, string $argument): void
    {

        // Dans le cas où on est en jeu, on revient dans le monde de sélection du personnage.
        if ($user->state() === ConnectionState::Ingame) {
            $characterName = $user->player()->character()->name;

            $this->gameWorld->exitWorld($user->player());
            $this->authWorld->enterWorld($user);

            $user->send(new StoppedPlaying($characterName));

            $this->characterListAction->onReceive($user, '');

            return;
        }

        // Si on est dans le monde de sélection du personnage et qu'on souhaite se deloguer, on sort complètement
        // de ce monde et on repasse en "connected", nécessitant de se reloguer.
        if ($user->state() === ConnectionState::Authed) {
            $this->authWorld->exitWorld($user);
            $user->send(new LoggedOut());

            return;
        }

        throw new \Exception('not handled !');

    }

}
