<?php

namespace App\Network\In\Authed;

use App\Entity\Character;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Authed\CharacterAlreadyExists;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\NoStartingRoom;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterCreate implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoomRepository $roomRepository,
        private readonly CharacterList $charactersCommand,
    ) {
    }

    public function name(): string
    {
        return 'character-create';
    }

    public function states(): array
    {
        return [TelnetState::Authed];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->client()->send(new Usage('character-create <name>'));
            $this->charactersCommand->execute($session, '');

            return;
        }

        $account = $session->account();
        $existing = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($existing !== null) {
            $session->client()->send(new CharacterAlreadyExists($name));
            $this->charactersCommand->execute($session, '');

            return;
        }

        $startingRoom = $this->roomRepository->findStartingRoom();

        if ($startingRoom === null) {
            $session->client()->send(new NoStartingRoom());
            $this->charactersCommand->execute($session, '');

            return;
        }

        $character = new Character($account, $startingRoom, $name, 100);
        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $session->client()->send(new CharacterCreated($name));
        $this->charactersCommand->execute($session, '');
    }
}
