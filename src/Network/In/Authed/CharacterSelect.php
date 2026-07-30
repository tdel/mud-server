<?php

namespace App\Network\In\Authed;

use App\Game\AuthWorld;
use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\In\Ingame\Look;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Authed\NowPlaying;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterSelect implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthWorld $authWorld,
        private readonly CharacterList $characterListAction,
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $user->send(new Usage('character-select <name>'));
            $this->characterListAction->onReceive($user, '');

            return;
        }

        $account = $user->account();

        $character = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($character === null) {
            $user->send(new NoCharacterNamed($name));
            $this->characterListAction->onReceive($user, '');

            return;
        }

        $account->setCurrentCharacter($character);
        $this->entityManager->flush();

        $this->authWorld->moveToGameWorld($user);

        $user->send(new NowPlaying($character->name));
        $this->lookCommand->onReceive($user, '');
    }
}
