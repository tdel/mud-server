<?php

namespace App\Auth;

use App\Entity\Account;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\OutputMessageInterface;
use App\Network\Telnet\OutputTelnetMessageInterface;
use App\Network\Telnet\TelnetSession;

/**
 * A connected endpoint that can receive messages, regardless of transport
 * (telnet, some future protocol) — with no notion of Character, since it's
 * used before a character has even been selected. Carries the Account it's
 * authenticated as (once past the "connected" state) so that AuthWorld and
 * GameWorld can both answer "is this account already in use?" without a
 * dedicated registry.
 */
final class Client
{
    private ?Account $account = null;
    private ?Player $player = null;
    private ConnectionState $state = ConnectionState::Connected;

    public function __construct(
        private readonly TelnetSession $session,
    ) {
    }

    public function send(OutputMessageInterface $message): void
    {
        if (!$message instanceof OutputTelnetMessageInterface) {
            throw new \LogicException(sprintf('%s cannot be sent over telnet.', $message::class));
        }

        $this->session->send($message, $this);
    }

    /**
     * @throws \RuntimeException if called before an account has been authenticated
     */
    public function account(): Account
    {
        if ($this->account === null) {
            throw new \RuntimeException('This client has no account yet.');
        }

        return $this->account;
    }

    public function accountOrNull(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    public function player(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): void
    {
        $this->player = $player;
    }

    public function state(): ConnectionState
    {
        return $this->state;
    }

    public function isState(ConnectionState $state): bool
    {
        return $this->state === $state;
    }

    public function setState(ConnectionState $state): void
    {
        $this->state = $state;
    }

    public function close(): void
    {
        $this->session->close();
    }

    /**
     * @param \Closure(string): void $handler
     */
    public function awaitLine(\Closure $handler): void
    {
        $this->session->awaitLine($handler);
    }

    /**
     * @param \Closure(string): void $onLine
     */
    public function promptMasked(string $prompt, \Closure $onLine): void
    {
        $this->session->promptMasked($prompt, $onLine);
    }
}
