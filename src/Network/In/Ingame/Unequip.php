<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Ingame\ItemNotCarried;
use App\Network\Out\Ingame\ItemNotEquipped;
use App\Network\Out\Ingame\ItemUnequipped;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Unequip implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'unequip';
    }

    public function states(): array
    {
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->player()->send(new Usage('unequip <name>'));

            return;
        }

        $character = $session->character();
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $character]);
        $item = $this->findByTemplateName($items, $name);

        if ($item === null) {
            $session->player()->send(new ItemNotCarried($name));

            return;
        }

        if ($item->slot === null) {
            $session->player()->send(new ItemNotEquipped($item->template->name));

            return;
        }

        $item->unequip();
        $this->entityManager->flush();

        $session->player()->send(new ItemUnequipped($item->template->name));
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
