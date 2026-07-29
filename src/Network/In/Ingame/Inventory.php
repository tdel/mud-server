<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\Inventory as InventoryMessage;
use Doctrine\ORM\EntityManagerInterface;

final class Inventory extends AbstractPlayerAction
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

    public function onPlayerAction(Player $player, string $argument): void
    {
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $player->character()]);

        $names = array_map(static fn (Item $item): string => $item->template->name, $items);

        $player->send(new InventoryMessage($names));
    }
}
