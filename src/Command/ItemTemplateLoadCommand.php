<?php

namespace App\Command;

use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:item-template:load', description: 'Load item templates from data/items.json')]
final class ItemTemplateLoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%/data/items.json')] private readonly string $itemsFile,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!is_file($this->itemsFile)) {
            $io->error(sprintf('File not found: %s', $this->itemsFile));

            return Command::FAILURE;
        }

        $entries = json_decode(file_get_contents($this->itemsFile), true, flags: JSON_THROW_ON_ERROR);

        $created = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $id = Uuid::fromString($entry['id']);

            if ($this->entityManager->find(ItemTemplate::class, $id) !== null) {
                $skipped++;

                continue;
            }

            $template = new ItemTemplate(
                $id,
                $entry['name'],
                $entry['description'] ?? null,
                ItemType::from($entry['type']),
                $entry['weight'],
            );
            $this->entityManager->persist($template);
            $created++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d item template(s) created, %d already present.', $created, $skipped));

        return Command::SUCCESS;
    }
}
