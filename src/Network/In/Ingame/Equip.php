<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Ingame\ItemEquipped;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Ingame\ItemNotEquippable;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Equip implements TelnetCommandInterface
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
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->player()->send(new Usage('equip <name>'));

            return;
        }

        $character = $session->character();
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $character]);
        $item = $this->findByTemplateName($items, $name);

        if ($item === null) {
            $session->player()->send(new ItemNotCarried($name));

            return;
        }

        $slot = $item->template->type->equipmentSlot();

        if ($slot === null) {
            $session->player()->send(new ItemNotEquippable($item->template->name));

            return;
        }

        foreach ($items as $existing) {
            if ($existing !== $item && $existing->slot === $slot) {
                $existing->unequip();
            }
        }

        $character->equip($item, $slot);
        $this->entityManager->flush();

        $session->player()->send(new ItemEquipped($item->template->name, $slot));
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
