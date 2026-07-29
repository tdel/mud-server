<?php

namespace App\Network\In\Authed;

use App\Auth\Client;
use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\Out\Authed\CharacterAlreadyExists;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\NoStartingRoom;
use App\Network\Out\Usage;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterCreate extends AbstractClientAction
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
        return [ConnectionState::Authed];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $client->send(new Usage('character-create <name>'));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $account = $client->account();

        $existing = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($existing !== null) {
            $client->send(new CharacterAlreadyExists($name));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $startingRoom = $this->roomRepository->findStartingRoom();

        if ($startingRoom === null) {
            $client->send(new NoStartingRoom());
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $character = new Character($account, $startingRoom, $name, 100);
        $this->entityManager->persist($character);
        $this->entityManager->flush();

        $client->send(new CharacterCreated($name));
        $this->charactersCommand->onClientAction($client, '');
    }
}
