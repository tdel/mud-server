<?php

namespace App\Network\Telnet;

use App\Doctrine\CoroutineEntityManagerRegistry;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\Server\Connection;

/**
 * Drives one telnet connection's coroutine: opens/closes its dedicated
 * EntityManager, reads raw bytes off the Swoole connection, strips telnet
 * IAC sequences, splits on newlines, and hands each line to the
 * connection's TelnetSession. The try/finally wraps the whole loop, not
 * just line handling, so a bug that somehow escapes TelnetSession::
 * handleLine()'s own try/catch still closes only this one connection,
 * never propagates up to Co\run() and takes the server down for everyone.
 */
#[WithMonologChannel('game')]
final class TelnetConnectionHandler
{
    private const int MAX_BUFFER = 1024;

    private string $buffer = '';

    public function __construct(
        private readonly Connection $connection,
        private readonly TelnetSession $session,
        private readonly CoroutineEntityManagerRegistry $entityManagerRegistry,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $this->entityManagerRegistry->open();

        $this->logger->info('telnet.connected', [
            'session' => $this->session->instanceId(),
            'remote' => $this->remoteAddress(),
        ]);

        $this->session->write("Welcome to mud-server.\nType \"login <name>\" or \"register <name>\" to begin.\n");

        try {
            while (true) {
                $chunk = $this->connection->recv();

                if ($chunk === '' || $chunk === false) {
                    break;
                }

                $this->onData($chunk);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('telnet.connection.error', [
                'session' => $this->session->instanceId(),
                'exception' => $e,
            ]);
        } finally {
            $this->session->handleClose();
            $this->entityManagerRegistry->close();
        }
    }

    private function onData(string $chunk): void
    {
        $this->buffer .= IacFilter::strip($chunk);

        if (strlen($this->buffer) > self::MAX_BUFFER) {
            $this->session->write("Line too long.\n");
            $this->connection->close();

            return;
        }

        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line = trim(substr($this->buffer, 0, $pos));
            $this->buffer = substr($this->buffer, $pos + 1);
            $this->session->handleLine($line);
        }
    }

    private function remoteAddress(): string
    {
        $peer = $this->connection->exportSocket()->getpeername();

        return sprintf('tcp://%s:%d', $peer['address'] ?? '?', $peer['port'] ?? 0);
    }
}
