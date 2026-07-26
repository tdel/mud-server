<?php

namespace App\Auth;

use App\Entity\Account;
use App\Network\OutputMessageInterface;

/**
 * A connected endpoint that can receive messages, regardless of transport
 * (telnet, some future protocol) — with no notion of Character, since it's
 * used before a character has even been selected. Carries the Account it's
 * authenticated as (once past the "connected" state) so that AuthWorld and
 * GameWorld can both answer "is this account already in use?" without a
 * dedicated registry.
 */
abstract class Client
{
    private ?Account $account = null;

    abstract public function send(OutputMessageInterface $message): void;

    public function account(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }
}
