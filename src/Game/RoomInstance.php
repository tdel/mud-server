<?php

namespace App\Game;

use App\Entity\Character;
use App\Entity\Room;
use App\Network\Out\Ingame\CharacterDisconnected;
use App\Network\Out\Ingame\CharacterJoinedRoom;
use App\Network\Out\Ingame\CharacterLeftRoom;
use App\Network\OutputMessageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Holds the players currently present in one room, and lets a command
 * broadcast a message to everyone in it. Joining/leaving always notifies
 * the room, so callers never need to remember to do it themselves.
 *
 * Runs under Swoole coroutines: any loop over $players is only safe as
 * long as nothing inside it can yield (findCharacterByName()/characters()
 * are pure in-memory today and fine as-is — if either ever gains a call
 * that can yield, it needs the same snapshot-before-iterating treatment
 * broadcast() uses).
 */
final class RoomInstance
{
    /** @var \SplObjectStorage<PlayerInstance, null> */
    private \SplObjectStorage $players;

    private readonly Uuid $roomId;

    public function __construct(
        Room $room,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->roomId = $room->id;
        $this->players = new \SplObjectStorage();
    }

    public function join(PlayerInstance $player): void
    {
        // GameWorld caches one RoomInstance per room for the whole server
        // lifetime, but under Swoole each coroutine gets its own
        // EntityManager (see App\Doctrine\EntityManagerProxy) with its own
        // identity map. Holding on to a Room *object* loaded by whichever
        // coroutine happened to construct this RoomInstance would make it
        // a foreign, unmanaged entity to every other coroutine's
        // EntityManager — Doctrine would refuse to flush it (cascade
        // persist error). getReference() resolves a proxy bound to
        // *this* call's own current EntityManager instead.
        $room = $this->entityManager->getReference(Room::class, $this->roomId);
        \assert($room !== null);

        $player->moveToRoom($room);
        $this->entityManager->flush();

        // attach() only after flush() returns: flush() can yield (DB
        // round-trip), and a half-joined player must not be visible to
        // characters()/broadcast()/findCharacterByName() calls made by
        // other coroutines scheduled during that yield. Keep this order.
        $this->players->attach($player);

        $this->broadcast(new CharacterJoinedRoom($player->character()->name), exclude: $player);
    }

    public function leave(PlayerInstance $player, Room $destination): void
    {
        $this->players->detach($player);

        $this->broadcast(new CharacterLeftRoom($player->character()->name, $destination->name), exclude: $player);
    }

    public function disconnect(PlayerInstance $player): void
    {
        $this->players->detach($player);

        $this->broadcast(new CharacterDisconnected($player->character()->name), exclude: $player);
    }

    public function findCharacterByName(string $name): ?Character
    {
        foreach ($this->players as $player) {
            if (strcasecmp($player->character()->name, $name) === 0) {
                return $player->character();
            }
        }

        return null;
    }

    /**
     * @return Character[]
     */
    public function characters(): array
    {
        $characters = [];
        foreach ($this->players as $player) {
            $characters[] = $player->character();
        }

        return $characters;
    }

    public function broadcast(OutputMessageInterface $message, ?PlayerInstance $exclude = null): void
    {
        // Snapshot before iterating: send() can yield (a slow/stalled
        // client), and another coroutine may attach()/detach() players in
        // $this->players while this broadcast is suspended mid-loop.
        // SplObjectStorage's iterator isn't safe across a live mutation.
        $recipients = iterator_to_array($this->players, preserve_keys: false);

        foreach ($recipients as $player) {
            if ($player === $exclude) {
                continue;
            }

            $player->send($message);
        }
    }
}
