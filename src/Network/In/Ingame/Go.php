<?php

namespace App\Network\In\Ingame;

use App\Entity\RoomExit;
use App\Game\GameWorld;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\NoSuchExit;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Go implements TelnetCommandInterface
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
        return [TelnetState::Ingame];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $direction = trim($argument);

        if ($direction === '') {
            $session->player()->send(new Usage('go <direction>'));

            return;
        }

        $player = $session->player();
        $character = $player->character();
        $oldRoom = $character->currentRoom;

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

        $character->moveToRoom($newRoom);
        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $this->gameWorld->channelFor($newRoom)->join($player);

        $this->lookCommand->execute($session, '');
    }
}
