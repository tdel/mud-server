<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\ItemEquipped;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Ingame\ItemNotEquippable;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class Equip implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $character]);
        $item = $this->findByTemplateName($items, $name);

        if ($item === null) {
            $player->send(new ItemNotCarried($name));

            return;
        }

        $slot = $item->template->type->equipmentSlot();

        if ($slot === null) {
            $player->send(new ItemNotEquippable($item->template->name));

            return;
        }

        foreach ($items as $existing) {
            if ($existing !== $item && $existing->slot === $slot) {
                $existing->unequip();
            }
        }

        $character->equip($item, $slot);
        $this->entityManager->flush();

        $player->send(new ItemEquipped($item->template->name, $slot));
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
