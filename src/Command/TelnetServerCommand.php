<?php

namespace App\Command;

use App\Doctrine\CoroutineEntityManagerRegistry;
use App\Game\AuthWorld;
use App\Game\GameWorld;
use App\Network\ActionDispatcher;
use App\Network\ActionRegistry;
use App\Network\Telnet\TelnetConnectionHandler;
use App\Network\Telnet\TelnetSession;
use App\Repository\RoomRepository;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
use Swoole\Coroutine\WaitGroup;
use Swoole\Process;
use Swoole\Runtime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function Co\run;

#[AsCommand(name: 'app:telnet:serve', description: 'Start the telnet MUD server')]
#[WithMonologChannel('game')]
final class TelnetServerCommand extends Command
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly ActionDispatcher $actionDispatcher,
        private readonly ActionRegistry $actionRegistry,
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly CoroutineEntityManagerRegistry $entityManagerRegistry,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'TELNET_HOST')] private readonly string $telnetHost,
        #[Autowire(env: 'int:TELNET_PORT')] private readonly int $telnetPort,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uri = sprintf('%s:%d', $this->telnetHost, $this->telnetPort);

        // Only the I/O this app actually performs inside a coroutine:
        // Doctrine's PDO pgsql driver and the server's own TCP sockets.
        // Not SWOOLE_HOOK_ALL — keep the hooked surface small and reviewable.
        Runtime::enableCoroutine(SWOOLE_HOOK_TCP | SWOOLE_HOOK_PDO_PGSQL | SWOOLE_HOOK_SLEEP);

        // Everything below touches the (now coroutine-hooked) PDO driver
        // sooner or later, directly or via the DB-backed startup checks —
        // it all has to run inside a coroutine, so the whole boot sequence
        // (not just $server->start()) is wrapped in run().
        $exitCode = Command::SUCCESS;

        run(function () use ($io, $uri, &$exitCode): void {
            if ($this->roomRepository->findStartingRoom() === null) {
                $this->logger->error('telnet.server.no_starting_room');
                $io->error('No starting room is configured. Run "make console app:room:create" first.');
                $exitCode = Command::FAILURE;

                return;
            }

            $this->actionRegistry->warmUp();
            $this->gameWorld->warmRoomInstances($this->roomRepository->findAll());

            try {
                $server = new Server($this->telnetHost, $this->telnetPort);
            } catch (\Throwable $e) {
                $this->logger->error('telnet.server.bind_failed', ['uri' => $uri, 'exception' => $e]);
                $io->error(sprintf('Could not start the telnet server on %s: %s', $uri, $e->getMessage()));
                $exitCode = Command::FAILURE;

                return;
            }

            // Single process, single coroutine server — deliberately not
            // worker_num > 1 (Swoole\Server): separate worker processes
            // would fragment GameWorld's in-memory shared state (players
            // in "the same" room on different workers would never see
            // each other) without a whole IPC layer this app doesn't have.
            $inFlight = new WaitGroup();

            $server->handle(function (Connection $connection) use ($inFlight): void {
                $inFlight->add();

                try {
                    $session = new TelnetSession($connection, $this->actionDispatcher, $this->gameWorld, $this->authWorld, $this->logger);
                    $handler = new TelnetConnectionHandler($connection, $session, $this->entityManagerRegistry, $this->logger);
                    $handler->run();
                } finally {
                    $inFlight->done();
                }
            });

            $shutdown = function () use ($server, $inFlight): void {
                $this->logger->info('telnet.server.shutting_down');
                $server->shutdown();
                $inFlight->wait(10.0);
            };
            Process::signal(SIGTERM, $shutdown);
            Process::signal(SIGINT, $shutdown);

            $this->logger->info('telnet.server.started', ['uri' => $uri]);
            $io->success(sprintf('Telnet server started on %s (Ctrl+C to stop).', $uri));

            $server->start();
        });

        return $exitCode;
    }
}
