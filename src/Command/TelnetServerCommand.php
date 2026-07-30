<?php

namespace App\Command;

use App\Game\AuthWorld;
use App\Game\GameWorld;
use App\Repository\RoomRepository;
use App\Network\ActionDispatcher;
use App\Network\Telnet\TelnetSession;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:telnet:serve', description: 'Start the telnet MUD server')]
#[WithMonologChannel('game')]
final class TelnetServerCommand extends Command
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly ActionDispatcher $actionDispatcher,
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'TELNET_HOST')] private readonly string $telnetHost,
        #[Autowire(env: 'int:TELNET_PORT')] private readonly int $telnetPort,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->roomRepository->findStartingRoom() === null) {
            $this->logger->error('telnet.server.no_starting_room');
            $io->error('No starting room is configured. Run "make console app:room:create" first.');

            return Command::FAILURE;
        }

        $uri = sprintf('%s:%d', $this->telnetHost, $this->telnetPort);

        try {
            $socket = new SocketServer($uri);
        } catch (\Throwable $e) {
            $this->logger->error('telnet.server.bind_failed', ['uri' => $uri, 'exception' => $e]);
            $io->error(sprintf('Could not start the telnet server on %s: %s', $uri, $e->getMessage()));

            return Command::FAILURE;
        }

        $socket->on('connection', function (ConnectionInterface $connection): void {
            new TelnetSession($connection, $this->actionDispatcher, $this->gameWorld, $this->authWorld, $this->logger);
        });

        $this->logger->info('telnet.server.started', ['uri' => $uri]);
        $io->success(sprintf('Telnet server started on %s (Ctrl+C to stop).', $uri));
        Loop::run();

        return Command::SUCCESS;
    }
}
