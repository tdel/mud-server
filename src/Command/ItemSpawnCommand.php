<?php

namespace App\Command;

use App\Game\GameWorld;
use App\Repository\ItemTemplateRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:item:spawn', description: 'Spawn an instance of an item template into a room')]
final class ItemSpawnCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameWorld $gameWorld,
        private readonly ItemTemplateRepository $itemTemplateRepository,
        private readonly RoomRepository $roomRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Item template name')
            ->addOption('room', null, InputOption::VALUE_REQUIRED, 'Room name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $templateName = $input->getOption('template') ?? $io->ask('Item template name');
        $template = $this->itemTemplateRepository->findOneByName($templateName);

        if ($template === null) {
            $io->error(sprintf('No item template named "%s".', $templateName));

            return Command::FAILURE;
        }

        $roomName = $input->getOption('room') ?? $io->ask('Room name');
        $room = $this->roomRepository->findOneByName($roomName);

        if ($room === null) {
            $io->error(sprintf('No room named "%s".', $roomName));

            return Command::FAILURE;
        }

        $item = $this->gameWorld->spawnItemInRoom($template, $room);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $io->success(sprintf('Spawned "%s" (id=%s) in room "%s".', $template->name, $item->id, $room->name));

        return Command::SUCCESS;
    }
}
