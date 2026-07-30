<?php

namespace App\Tests\Support;

use App\Entity\Account;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\OutputMessageInterface;
use App\Network\UserInterface;

/**
 * Fake UserInterface for tests that dispatch a real Action and want to
 * inspect what it sent, without a real telnet socket. `awaitLine()` and
 * `promptMasked()` capture the callback instead of invoking it, so a test
 * can drive a multi-step prompt manually via answerAwaitedLine()/answerMaskedPrompt().
 */
final class InMemoryUser implements UserInterface
{
    /** @var list<OutputMessageInterface> */
    public array $sent = [];

    private ConnectionState $state = ConnectionState::Connected;
    private ?Account $account = null;
    private ?PlayerInstance $player = null;
    private ?\Closure $awaitedLine = null;
    private ?\Closure $maskedPromptLine = null;

    public function awaitLine(\Closure $handler): void
    {
        $this->awaitedLine = $handler;
    }

    public function answerAwaitedLine(string $line): void
    {
        $handler = $this->awaitedLine;
        $this->awaitedLine = null;

        \assert($handler !== null, 'No line is currently being awaited.');
        $handler($line);
    }

    public function account(): Account
    {
        \assert($this->account !== null, 'No account attached.');

        return $this->account;
    }

    public function player(): PlayerInstance
    {
        \assert($this->player !== null, 'No player attached.');

        return $this->player;
    }

    public function attachPlayer(PlayerInstance $player): void
    {
        $this->player = $player;
    }

    public function state(): ConnectionState
    {
        return $this->state;
    }

    public function setState(ConnectionState $state): void
    {
        $this->state = $state;
    }

    public function attachAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function send(OutputMessageInterface $message): void
    {
        $this->sent[] = $message;
    }

    public function lastSent(): ?OutputMessageInterface
    {
        return $this->sent === [] ? null : $this->sent[array_key_last($this->sent)];
    }

    /**
     * Some actions send a message and then, unconditionally, a trailing
     * one (e.g. CharacterCreate always re-sends the character list last),
     * so lastSent() alone can't be used to assert on the specific message
     * a given branch produced — this searches the whole history instead.
     *
     * @param class-string<OutputMessageInterface> $class
     */
    public function hasSent(string $class): bool
    {
        foreach ($this->sent as $message) {
            if ($message instanceof $class) {
                return true;
            }
        }

        return false;
    }

    public function promptMasked(string $prompt, \Closure $onLine): void
    {
        $this->maskedPromptLine = $onLine;
    }

    public function answerMaskedPrompt(string $line): void
    {
        $handler = $this->maskedPromptLine;
        $this->maskedPromptLine = null;

        \assert($handler !== null, 'No masked prompt is currently pending.');
        $handler($line);
    }

    public function close(): void
    {
    }
}
