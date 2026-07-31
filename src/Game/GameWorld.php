<?php

namespace App\Game;

use App\Entity\Account;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tracks every player currently in the game world, for as long as the
 * server process runs. Transport-agnostic: only knows about PlayerInstance and
 * Room, never about telnet (or any other transport) directly. Keyed by
 * room id (not object identity) since Doctrine may hand back a different
 * Room instance for the same row after an EntityManager reset.
 */
final class GameWorld
{
    /** @var array<string, RoomInstance> */
    private array $roomInstances = [];

    /** @var \SplObjectStorage<PlayerInstance, null> */
    private \SplObjectStorage $players;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->players = new \SplObjectStorage();
    }

    public function enterWorld(PlayerInstance $player): void
    {
        $this->players->attach($player);
        $this->roomInstance($player->currentRoom())->join($player);
    }

    public function exitWorld(PlayerInstance $player): void
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

    /**
     * Called once at server boot, before any connection is accepted, so
     * roomInstance()'s lazy `??=` never has to run concurrently — two
     * coroutines racing to visit the same room for the first time could
     * otherwise both pass the "not cached yet" check before either
     * assignment lands, silently losing one RoomInstance (and whichever
     * players had already joined it).
     *
     * @param iterable<Room> $rooms
     */
    public function warmRoomInstances(iterable $rooms): void
    {
        foreach ($rooms as $room) {
            $this->roomInstance($room);
        }
    }

    // Pure in-memory comparisons, no yield point in the loop — safe under
    // coroutines as written. If this ever grows a DB call inside the loop,
    // revisit: a coroutine yielding mid-scan could see $players mutated by
    // another coroutine's attach()/detach() while iterating.
    public function isAlreadyConnected(Account $account): bool
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
