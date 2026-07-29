<?php

namespace App\Auth;

use App\Entity\Account;
use App\Network\ConnectionState;

/**
 * Tracks every client that is connected but not currently playing (states
 * "connected" and "authed" both count as being in this world).
 */
final class AuthWorld
{
    /** @var \SplObjectStorage<Client, null> */
    private \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function enterWorld(Client $client): void
    {
        $this->clients->attach($client);
    }

    public function exitWorld(Client $client): void
    {
        $this->clients->detach($client);
    }

    public function isAccountConnected(Account $account): bool
    {
        foreach ($this->clients as $client) {
            if ($client->isState(ConnectionState::Connected)) {
                continue;
            }

            if ($client->account()->id->equals($account->id)) {
                return true;
            }
        }

        return false;
    }
}
