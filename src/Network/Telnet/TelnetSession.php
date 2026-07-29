<?php

namespace App\Network\Telnet;

use App\Auth\AuthWorld;
use App\Auth\Client;
use App\Entity\Account;
use App\Entity\Character;
use App\Game\GameWorld;
use App\Game\Player;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;

final class TelnetSession implements TelnetOutputInterface
{
    private const int MAX_BUFFER = 1024;

    private string $buffer = '';
    private TelnetState $state = TelnetState::Connected;
    private readonly Client $client;
    private ?Player $player = null;
    /** @var \Closure(string): void|null */
    private ?\Closure $pendingLine = null;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly TelnetCommandRegistry $commandRegistry,
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new TelnetClient($this);
        $this->authWorld->enterWorld($this->client);

        $this->write("Welcome to mud-server.\nType \"login <name>\" or \"register <name>\" to begin.\n");

        $connection->on('data', function (string $chunk): void {
            $this->onData($chunk);
        });
        $connection->on('error', function (\Throwable $e): void {
            $this->logger->warning('Telnet connection error', ['exception' => $e]);
        });
        $connection->on('close', function (): void {
            if ($this->player !== null) {
                $this->gameWorld->exitWorld($this->player);
            }
            $this->authWorld->exitWorld($this->client);
        });
    }

    private function onData(string $chunk): void
    {
        $this->buffer .= IacFilter::strip($chunk);

        if (strlen($this->buffer) > self::MAX_BUFFER) {
            $this->write("Line too long.\n");
            $this->connection->close();

            return;
        }

        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line = trim(substr($this->buffer, 0, $pos));
            $this->buffer = substr($this->buffer, $pos + 1);
            $this->handleLine($line);
        }
    }

    private function handleLine(string $line): void
    {
        if ($this->pendingLine !== null) {
            $handler = $this->pendingLine;
            $this->pendingLine = null;
            $handler($line);

            return;
        }

        [$name, $argument] = explode(' ', $line, 2) + [1 => ''];
        $command = $this->commandRegistry->find($this->state, strtolower($name));

        if ($command === null) {
            $this->write("Unknown command.\n");
            if ($this->state !== TelnetState::Connected) {
                $this->write('> ');
            }

            return;
        }

        $command->execute($this, $argument);
    }

    public function state(): TelnetState
    {
        return $this->state;
    }

    public function setState(TelnetState $state): void
    {
        $this->state = $state;
    }

    public function setAccount(?Account $account): void
    {
        $this->client->setAccount($account);
    }

    public function setPlayer(?Player $player): void
    {
        $this->player = $player;
    }

    /**
     * @param \Closure(string): void $handler
     */
    public function awaitLine(\Closure $handler): void
    {
        $this->pendingLine = $handler;
    }

    /**
     * Writes $prompt with client-side echo suppressed, then captures the
     * next raw line as $onLine's argument. Echo is always restored right
     * before $onLine runs, regardless of which branch it takes. Since the
     * client never echoed the user's Enter keypress while echo was off, we
     * emit our own newline so whatever $onLine writes starts on a fresh
     * line instead of being appended right after the prompt.
     *
     * @param \Closure(string): void $onLine
     */
    public function promptMasked(string $prompt, \Closure $onLine): void
    {
        $this->write(TelnetEcho::OFF);
        $this->write($prompt);
        $this->awaitLine(function (string $line) use ($onLine): void {
            $this->write(TelnetEcho::ON);
            $this->write("\n");
            $onLine($line);
        });
    }

    public function send(OutputTelnetMessageInterface $message, Client $client): void
    {
        $message->toTelnet($this, $client);
        $this->write('> ');
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function account(): Account
    {
        $account = $this->client->account();
        \assert($account !== null);

        return $account;
    }

    public function player(): Player
    {
        \assert($this->player !== null);

        return $this->player;
    }

    public function character(): Character
    {
        return $this->player()->character();
    }

    public function close(): void
    {
        $this->connection->end();
    }

    public function write(string $text): void
    {
        $this->connection->write($text);
    }
}
