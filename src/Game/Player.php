<?php

namespace App\Game;

use App\Auth\Client;
use App\Entity\Character;
use App\Entity\Room;
use App\Network\OutputMessageInterface;

/**
 * A connected player, regardless of which transport (telnet, some future
 * protocol) it's connected through — delegated entirely to its Client. A
 * Player is tied to one Character for its whole lifetime — switching
 * characters means logging out and selecting another one, which creates a
 * new Player.
 */
final class Player
{
    public function __construct(
        private readonly Character $character,
        private readonly Client $client,
    ) {
    }

    public function character(): Character
    {
        return $this->character;
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function currentRoom(): Room
    {
        return $this->character->currentRoom;
    }

    public function moveToRoom(Room $room): void
    {
        $this->character->moveToRoom($room);
    }

    public function send(OutputMessageInterface $message): void
    {
        $this->client->send($message);
    }
}
