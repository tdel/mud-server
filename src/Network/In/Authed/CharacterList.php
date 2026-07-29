<?php

namespace App\Network\In\Authed;

use App\Auth\Client;
use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\Out\Authed\CharacterList as CharacterListMessage;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterList extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function name(): string
    {
        return 'characters-list';
    }

    public function states(): array
    {
        return [ConnectionState::Authed];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $account = $client->account();

        $characters = $this->entityManager->getRepository(Character::class)->findBy(['account' => $account]);

        $names = array_map(static fn (Character $character): string => $character->name, $characters);

        $client->send(new CharacterListMessage($names));
    }
}
