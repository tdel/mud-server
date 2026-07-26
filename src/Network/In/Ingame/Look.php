<?php

namespace App\Network\In\Ingame;

use App\Entity\Character;
use App\Entity\Item;
use App\Entity\RoomExit;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\RoomDescription;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Look implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'look';
    }

    public function states(): array
    {
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $session->player()->send($this->describeRoom($session->character()));
    }

    private function describeRoom(Character $character): RoomDescription
    {
        $room = $character->currentRoom;

        $exits = $this->entityManager->getRepository(RoomExit::class)->findBy(['sourceRoom' => $room]);
        $characters = $this->entityManager->getRepository(Character::class)->findBy(['currentRoom' => $room]);
        $items = $this->entityManager->getRepository(Item::class)->findBy(['room' => $room]);

        $exitNames = array_map(static fn (RoomExit $exit): string => $exit->direction, $exits);
        $characterNames = array_map(
            static fn (Character $other): string => $other->name,
            array_filter($characters, static fn (Character $other): bool => $other !== $character),
        );
        $itemNames = array_map(static fn (Item $item): string => $item->template->name, $items);

        return new RoomDescription($room->name, $room->description, $exitNames, array_values($characterNames), $itemNames);
    }
}
