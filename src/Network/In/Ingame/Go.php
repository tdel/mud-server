<?php

namespace App\Network\In\Ingame;

use App\Entity\RoomExit;
use App\Game\GameWorld;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractPlayerAction;
use App\Network\Out\Ingame\NoSuchExit;
use App\Network\Out\Usage;
use Doctrine\ORM\EntityManagerInterface;

final class Go extends AbstractPlayerAction
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

    public function onPlayerAction(Player $player, string $argument): void
    {
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

        $this->gameWorld->channelFor($oldRoom)->leave($player, $newRoom);
        $this->gameWorld->channelFor($newRoom)->join($player);

        $this->lookCommand->onPlayerAction($player, '');
    }
}
