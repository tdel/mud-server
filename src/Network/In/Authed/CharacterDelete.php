<?php

namespace App\Network\In\Authed;

use App\Auth\Client;
use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\Out\Authed\CharacterDeleted;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Usage;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterDelete extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterList $charactersCommand,
    ) {
    }

    public function name(): string
    {
        return 'character-delete';
    }

    public function states(): array
    {
        return [ConnectionState::Authed];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $client->send(new Usage('character-delete <name>'));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        $account = $client->account();

        $character = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($character === null) {
            $client->send(new NoCharacterNamed($name));
            $this->charactersCommand->onClientAction($client, '');

            return;
        }

        if ($account->currentCharacter !== null && $account->currentCharacter->id->equals($character->id)) {
            $account->clearCurrentCharacter();
            $this->entityManager->persist($account);
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        $client->send(new CharacterDeleted($name));
        $this->charactersCommand->onClientAction($client, '');
    }
}
