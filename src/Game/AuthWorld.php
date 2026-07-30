<?php

namespace App\Game;

use App\Entity\Account;
use App\Network\ConnectionState;
use App\Network\Out\Connected\WelcomeBack;
use App\Network\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks every client that is connected but not currently playing (states
 * "connected" and "authed" both count as being in this world).
 */
final class AuthWorld
{
    /** @var \SplObjectStorage<UserInterface, null> */
    private \SplObjectStorage $connectedUsers;

    public function __construct(
        private readonly GameWorld $gameWorld
    )
    {
        $this->connectedUsers = new \SplObjectStorage();
    }

    public function enterWorld(UserInterface $user): void
    {
        $this->connectedUsers->attach($user);
        $user->setState(ConnectionState::Authed);
    }

    public function exitWorld(UserInterface $user): void
    {
        $this->connectedUsers->detach($user);
        $user->setState(ConnectionState::Connected);
    }

    public function moveToGameWorld(UserInterface $user): void
    {
        $this->connectedUsers->detach($user);

        $player = new PlayerInstance($user);
        $user->setState(ConnectionState::Ingame);

        $this->gameWorld->enterWorld($player);
    }

    public function isAlreadyConnected(Account $account): bool
    {
        foreach ($this->connectedUsers as $connectedUser) {
            if ($connectedUser->account()->id->equals($account->id)) {
                return true;
            }
        }

        return false;
    }
}
