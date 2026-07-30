<?php

namespace App\Network\In\Ingame;

use App\Game\ItemService;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemDropped;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Drop implements ActionInterface
{
    public function __construct(
        private readonly ItemService $itemService,
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
        $item = $this->itemService->findItemByName($character, $name);

        if ($item === null) {
            $player->send(new ItemNotCarried($name));

            return;
        }

        $this->itemService->removeItemFromInventory($item, $character);

        $player->send(new ItemDropped($item->template->name));
    }
}
