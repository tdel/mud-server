<?php

namespace App\Game;

use App\Entity\Room;
use App\Network\Out\CharacterDisconnected;
use App\Network\Out\CharacterJoinedRoom;
use App\Network\Out\CharacterLeftRoom;
use App\Network\OutputMessageInterface;

/**
 * Holds the players currently present in one room, and lets a command
 * broadcast a message to everyone in it. Joining/leaving always notifies
 * the room, so callers never need to remember to do it themselves.
 */
final class RoomChannel
{
    /** @var \SplObjectStorage<Player, null> */
    private \SplObjectStorage $players;

    public function __construct()
    {
        $this->players = new \SplObjectStorage();
    }

    public function join(Player $player): void
    {
        $this->players->attach($player);

        $this->broadcast(new CharacterJoinedRoom($player->character()->name), exclude: $player);
    }

    public function leave(Player $player, Room $destination): void
    {
        $this->players->detach($player);

        $this->broadcast(new CharacterLeftRoom($player->character()->name, $destination->name), exclude: $player);
    }

    public function disconnect(Player $player): void
    {
        $this->players->detach($player);

        $this->broadcast(new CharacterDisconnected($player->character()->name), exclude: $player);
    }

    public function broadcast(OutputMessageInterface $message, ?Player $exclude = null): void
    {
        foreach ($this->players as $player) {
            if ($player === $exclude) {
                continue;
            }

            $player->send($message);
        }
    }
}
