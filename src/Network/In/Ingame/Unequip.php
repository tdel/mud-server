<?php

namespace App\Network\In\Ingame;

use App\Game\ItemService;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Ingame\ItemNotEquipped;
use App\Network\Out\Ingame\ItemUnequipped;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Unequip implements ActionInterface
{
    public function __construct(
        private readonly ItemService $itemService,
    ) {
    }

    public function name(): string
    {
        return 'unequip';
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
            $player->send(new Usage('unequip <name>'));

            return;
        }

        $character = $player->character();
        $item = $this->itemService->findItemByName($character, $name);

        if ($item === null) {
            $player->send(new ItemNotCarried($name));

            return;
        }

        if ($item->slot === null) {
            $player->send(new ItemNotEquipped($item->template->name));

            return;
        }

        $this->itemService->unequipItem($item);

        $player->send(new ItemUnequipped($item->template->name));
    }
}
