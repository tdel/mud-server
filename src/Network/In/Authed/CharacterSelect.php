<?php

namespace App\Network\In\Authed;

use App\Auth\AuthWorld;
use App\Auth\Client;
use App\Entity\Character;
use App\Game\GameWorld;
use App\Game\Player;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\In\Ingame\Look;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Authed\NowPlaying;
use App\Network\Out\Usage;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterSelect extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameWorld $gameWorld,
        private readonly AuthWorld $authWorld,
        private readonly CharacterList $charactersCommand,
        private readonly Look $lookCommand,
    ) {
    }

    public function name(): string
    {
        return 'character-select';
    }

    public function states(): array
    {
        return [ConnectionState::Authed];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $client->send(new Usage('character-select <name>'));
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

        $account->setCurrentCharacter($character);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $player = new Player($character, $client);
        $this->authWorld->exitWorld($client);
        $this->gameWorld->enterWorld($player);

        $client->setPlayer($player);
        $client->setState(ConnectionState::Ingame);

        $client->send(new NowPlaying($character->name));
        $this->lookCommand->onPlayerAction($player, '');
    }
}
