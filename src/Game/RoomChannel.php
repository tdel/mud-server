<?php

namespace App\Game;

use App\Network\OutputMessageInterface;

/**
 * Holds the players currently present in one room, and lets a command
 * broadcast a message to everyone in it.
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
    }

    public function leave(Player $player): void
    {
        $this->players->detach($player);
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
