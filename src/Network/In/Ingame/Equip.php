<?php

namespace App\Network\In\Ingame;

use App\Game\ItemService;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemEquipped;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Ingame\ItemNotEquippable;
use App\Network\Out\Usage;
use App\Network\UserInterface;

final class Equip implements ActionInterface
{
    public function __construct(
        private readonly ItemService $itemService,
    ) {
    }

    public function name(): string
    {
        return 'equip';
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
            $player->send(new Usage('equip <name>'));

            return;
        }

        $character = $player->character();
        $item = $this->itemService->findItemByName($character, $name);

        if ($item === null) {
            $player->send(new ItemNotCarried($name));

            return;
        }

        $slot = $this->itemService->equipItem($item, $character);

        if ($slot === null) {
            $player->send(new ItemNotEquippable($item->template->name));

            return;
        }

        $player->send(new ItemEquipped($item->template->name, $slot));
    }
}
