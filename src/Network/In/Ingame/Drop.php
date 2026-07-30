<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemDropped;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class Drop implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'drop';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

        $name = trim($argument);

        if ($name === '') {
            $player->send(new Usage('drop <name>'));

            return;
        }

        $character = $player->character();
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $character]);
        $item = $this->findByTemplateName($items, $name);

        if ($item === null) {
            $player->send(new ItemNotCarried($name));

            return;
        }

        $item->moveToRoom($character->currentRoom);
        $this->entityManager->flush();

        $player->send(new ItemDropped($item->template->name));
    }

    /**
     * @param Item[] $items
     */
    private function findByTemplateName(array $items, string $name): ?Item
    {
        foreach ($items as $item) {
            if (strcasecmp($item->template->name, $name) === 0) {
                return $item;
            }
        }

        return null;
    }
}
