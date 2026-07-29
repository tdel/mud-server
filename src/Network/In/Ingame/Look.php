<?php

namespace App\Network\In\Ingame;

use App\Entity\Character;
use App\Entity\Item;
use App\Entity\RoomExit;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\RoomDescription;
use Doctrine\ORM\EntityManagerInterface;

final class Look extends AbstractPlayerAction
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
        return [ConnectionState::Ingame];
    }

    public function onPlayerAction(Player $player, string $argument): void
    {
        $player->send($this->describeRoom($player->character()));
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
