<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Game\ItemService;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\Inventory as InventoryMessage;
use App\Network\UserInterface;

final class Inventory implements ActionInterface
{
    public function __construct(
        private readonly ItemService $itemService,
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

        $items = $this->itemService->getInventory($player->character());

        $names = array_map(static fn (Item $item): string => $item->template->name, $items);

        $player->send(new InventoryMessage($names));
    }
}
