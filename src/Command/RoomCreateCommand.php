<?php

namespace App\Command;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:room:create', description: 'Create a room, optionally marking it as the starting room')]
final class RoomCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoomRepository $roomRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Room name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Room description')
            ->addOption('starting', null, InputOption::VALUE_NONE, 'Mark this room as the starting room');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getOption('name') ?? $io->ask('Room name');
        $description = $input->getOption('description') ?? $io->ask('Room description');

        $existingStarting = $this->roomRepository->findStartingRoom();
        $markStarting = (bool) $input->getOption('starting');

        if ($existingStarting === null && !$markStarting) {
            $markStarting = $io->confirm('No starting room exists yet. Make this the starting room?', true);
        }

        if ($markStarting && $existingStarting !== null) {
            if (!$io->confirm(sprintf('A starting room already exists ("%s"). Replace it?', $existingStarting->name), false)) {
                $markStarting = false;
            }
        }

        $room = new Room($name, $description);
        if ($markStarting) {
            $existingStarting?->unmarkAsStartingRoom();
            $room->markAsStartingRoom();
        }

        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $io->success(sprintf(
            'Room "%s" created (id=%s)%s.',
            $name,
            $room->id,
            $markStarting ? ', marked as the starting room' : '',
        ));

        return Command::SUCCESS;
    }
}
