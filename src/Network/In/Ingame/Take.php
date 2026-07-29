<?php

namespace App\Network\In\Ingame;

use App\Entity\Item;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Ingame\ItemNotFound;
use App\Network\Out\Ingame\ItemTaken;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Take implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'take';
    }

    public function states(): array
    {
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->player()->send(new Usage('take <name>'));

            return;
        }

        $character = $session->character();
        $items = $this->entityManager->getRepository(Item::class)->findBy(['room' => $character->currentRoom]);
        $item = $this->findByTemplateName($items, $name);

        if ($item === null) {
            $session->player()->send(new ItemNotFound($name));

            return;
        }

        $item->moveToCharacter($character);
        $this->entityManager->flush();

        $session->player()->send(new ItemTaken($item->template->name));
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
