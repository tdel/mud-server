<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\Inventory as InventoryMessage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class Inventory implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'inventory';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();

        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $player->character()]);

        $names = array_map(static fn (Item $item): string => $item->template->name, $items);

        $player->send(new InventoryMessage($names));
    }
}
