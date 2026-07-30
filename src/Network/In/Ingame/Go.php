<?php

namespace App\Network\In\Ingame;

use App\Entity\RoomExit;
use App\Game\GameWorld;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Ingame\NoSuchExit;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class Go implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameWorld $gameWorld,
        private readonly Look $lookCommand,
    ) {
    }

    public function name(): string
    {
        return 'go';
    }

    public function states(): array
    {
        return [ConnectionState::Ingame];
    }

    public function onReceive(UserInterface $user, string $argument): void
    {
        $player = $user->player();
        $direction = trim($argument);

        if ($direction === '') {
            $player->send(new Usage('go <direction>'));

            return;
        }

        $oldRoom = $player->currentRoom();

        $exit = $this->entityManager->getRepository(RoomExit::class)->findOneBy([
            'sourceRoom' => $oldRoom,
            'direction' => $direction,
        ]);

        if ($exit === null) {
            $player->send(new NoSuchExit($direction));

            return;
        }

        $newRoom = $exit->targetRoom;

        $this->gameWorld->roomInstance($oldRoom)->leave($player, $newRoom);
        $this->gameWorld->roomInstance($newRoom)->join($player);

        $this->lookCommand->onReceive($user, '');
    }
}
