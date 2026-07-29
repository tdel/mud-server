<?php

namespace App\Game;

use App\Entity\Account;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tracks every player currently in the game world, for as long as the
 * server process runs. Transport-agnostic: only knows about Player and
 * Room, never about telnet (or any other transport) directly. Keyed by
 * room id (not object identity) since Doctrine may hand back a different
 * Room instance for the same row after an EntityManager reset.
 */
final class GameWorld
{
    /** @var array<string, RoomInstance> */
    private array $roomInstances = [];

    /** @var \SplObjectStorage<Player, null> */
    private \SplObjectStorage $players;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->players = new \SplObjectStorage();
    }

    public function enterWorld(Player $player): void
    {
        $this->players->attach($player);
        $this->roomInstance($player->currentRoom())->join($player);
    }

    public function exitWorld(Player $player): void
    {
        if (!$this->players->contains($player)) {
            return;
        }

        $this->roomInstance($player->currentRoom())->disconnect($player);
        $this->players->detach($player);
    }

    public function roomInstance(Room $room): RoomInstance
    {
        return $this->roomInstances[$room->id->toString()] ??= new RoomInstance($room, $this->entityManager);
    }

    public function isAccountConnected(Account $account): bool
    {
        foreach ($this->players as $player) {
            if ($player->character()->account->id->equals($account->id)) {
                return true;
            }
        }

        return false;
    }

    public function spawnItemInRoom(ItemTemplate $template, Room $room): Item
    {
        $item = new Item($template);
        $item->moveToRoom($room);

        return $item;
    }
}
