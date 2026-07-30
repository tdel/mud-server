<?php

namespace App\Network\Telnet;

use App\Game\AuthWorld;
use App\Entity\Account;
use App\Game\GameWorld;
use App\Game\PlayerInstance;
use App\Network\ActionDispatcher;
use App\Network\ConnectionState;
use App\Network\OutputMessageInterface;
use App\Network\UserInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;
use Symfony\Component\Uid\Uuid;

#[WithMonologChannel('game')]
final class TelnetSession implements UserInterface, TelnetOutputInterface
{
    private const int MAX_BUFFER = 1024;

    private readonly string $instanceId;
    private string $buffer = '';
    private ?Account $account = null;
    private ?PlayerInstance $playerInstance = null;

    /** @var \Closure(string): void|null */
    private ?\Closure $pendingLine = null;

    private ConnectionState $state;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly ActionDispatcher $actionDispatcher,
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly LoggerInterface $logger,
    ) {
        $this->instanceId = Uuid::v7()->toRfc4122();
        $this->state = ConnectionState::Connected;

        $this->logger->info('telnet.connected', [
            'session' => $this->instanceId,
            'remote' => $connection->getRemoteAddress(),
        ]);

        $this->write("Welcome to mud-server.\nType \"login <name>\" or \"register <name>\" to begin.\n");

        $connection->on('data', function (string $chunk): void {
            $this->onData($chunk);
        });
        $connection->on('error', function (\Throwable $e): void {
            $this->logger->warning('telnet.connection.error', [
                'session' => $this->instanceId,
                'exception' => $e,
            ]);
        });
        $connection->on('close', function (): void {
            if ($this->playerInstance !== null) {
                $this->gameWorld->exitWorld($this->playerInstance);
            }
            $this->authWorld->exitWorld($this);

            $this->logger->info('telnet.disconnected', [
                'session' => $this->instanceId,
            ]);

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
        try {
            if ($this->pendingLine !== null) {
                $handler = $this->pendingLine;
                $this->pendingLine = null;
                $handler($line);

                return;
            }

            [$name, $argument] = explode(' ', $line, 2) + [1 => ''];

            $this->logger->info('telnet.command', [
                'session' => $this->instanceId,
                'command' => strtolower($name),
                'argument' => $argument,
            ]);

            $this->actionDispatcher->dispatch($this, strtolower($name), $argument);
        } catch (\Throwable $e) {
            $this->logger->error('telnet.command.failed', [
                'session' => $this->instanceId,
                'line' => $line,
                'exception' => $e,
            ]);
            $this->write("Something went wrong processing that command. Please try again.\n");
        }
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

    public function send(OutputMessageInterface $message): void
    {
        if (! ($message instanceof OutputTelnetMessageInterface)) {
            throw new \Exception('...'); // better exception here !
        }

        $message->toTelnet($this);
        $this->write('> ');
    }

    public function close(): void
    {
        $this->connection->end();
    }

    public function write(string $text): void
    {
        $this->connection->write($text);
    }

    public function state(): ConnectionState
    {
        return $this->state;
    }

    #[\Override]
    public function account(): Account
    {
        return $this->account;
    }

    #[\Override]
    public function player(): PlayerInstance
    {
        return $this->playerInstance;
    }

    #[\Override]
    public function attachAccount(Account $account): void
    {
        $this->account = $account;
    }

    #[\Override]
    public function attachPlayer(PlayerInstance $player): void
    {
        $this->playerInstance = $player;
    }

    #[\Override]
    public function setState(ConnectionState $state): void
    {
        $this->state = $state;

        switch ($state) {
            case ConnectionState::Connected:
                $this->account = null;
                $this->playerInstance = null;
                break;
            case ConnectionState::Authed:
                $this->playerInstance = null;
                break;
            case ConnectionState::Ingame:
                break;

            default:
                throw new \RuntimeException('...?');
        }

    }
}
