<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Ingame\Inventory as InventoryMessage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Inventory implements TelnetCommandInterface
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
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $items = $this->entityManager->getRepository(Item::class)->findBy(['character' => $session->character()]);

        $names = array_map(static fn (Item $item): string => $item->template->name, $items);

        $session->player()->send(new InventoryMessage($names));
    }
}
