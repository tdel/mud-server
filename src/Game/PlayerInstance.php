<?php

namespace App\Game;

use App\Entity\Character;
use App\Entity\Room;
use App\Network\OutputMessageInterface;
use App\Network\UserInterface;

/**
 * A connected player, regardless of which transport (telnet, some future
 * protocol) it's connected through — delegated entirely to its Client. A
 * PlayerInstance is tied to one Character for its whole lifetime — switching
 * characters means logging out and selecting another one, which creates a
 * new PlayerInstance.
 */
final class PlayerInstance
{
    public function __construct(
        private readonly UserInterface $user,
    ) {
        $user->attachPlayer($this);
    }

    public function character(): Character
    {
        return $this->user->account()->currentCharacter;
    }

    public function currentRoom(): Room
    {
        return $this->character()->currentRoom;
    }

    public function moveToRoom(Room $room): void
    {
        $this->character()->moveToRoom($room);
    }

    public function send(OutputMessageInterface $message): void
    {
        $this->user->send($message);
    }
}
