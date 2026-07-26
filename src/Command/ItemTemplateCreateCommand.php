<?php

namespace App\Command;

use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Repository\ItemTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:item-template:create', description: 'Create an item template (catalog entry)')]
final class ItemTemplateCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ItemTemplateRepository $itemTemplateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Template name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Template description')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, sprintf('Item type (%s)', implode(', ', array_column(ItemType::cases(), 'value'))))
            ->addOption('weight', null, InputOption::VALUE_REQUIRED, 'Weight');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getOption('name') ?? $io->ask('Template name');

        if ($this->itemTemplateRepository->findOneByName($name) !== null) {
            $io->error(sprintf('An item template named "%s" already exists.', $name));

            return Command::FAILURE;
        }

        $description = $input->getOption('description') ?? $io->ask('Template description');

        $typeOption = $input->getOption('type') ?? $io->choice('Type', array_column(ItemType::cases(), 'value'));
        $type = ItemType::tryFrom($typeOption);

        if ($type === null) {
            $io->error(sprintf('Unknown type "%s". Valid types: %s.', $typeOption, implode(', ', array_column(ItemType::cases(), 'value'))));

            return Command::FAILURE;
        }

        $weight = (int) ($input->getOption('weight') ?? $io->ask('Weight'));

        $template = new ItemTemplate(Uuid::v7(), $name, $description, $type, $weight);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $io->success(sprintf('Item template "%s" created (id=%s).', $name, $template->id));

        return Command::SUCCESS;
    }
}
