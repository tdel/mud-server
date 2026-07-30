<?php

namespace App\Network\In\Ingame;

use App\Game\ItemService;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemNotFound;
use App\Network\Out\Ingame\ItemTaken;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Take implements ActionInterface
{
    public function __construct(
        private readonly ItemService $itemService,
    ) {
    }

    public function name(): string
    {
        return 'take';
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
            $player->send(new Usage('take <name>'));

            return;
        }

        $character = $player->character();
        $item = $this->itemService->findItemInRoomByName($character->currentRoom, $name);

        if ($item === null) {
            $player->send(new ItemNotFound($name));

            return;
        }

        $this->itemService->addItemToInventory($item, $character);

        $player->send(new ItemTaken($item->template->name));
    }
}
