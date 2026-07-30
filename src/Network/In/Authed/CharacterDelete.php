<?php

namespace App\Network\In\Authed;

use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Authed\CharacterDeleted;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterDelete implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterList $characterListAction,
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $user->send(new Usage('character-delete <name>'));
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

        if ($account->currentCharacter !== null && $account->currentCharacter->id->equals($character->id)) {
            $account->clearCurrentCharacter();
            $this->entityManager->persist($account);
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        $user->send(new CharacterDeleted($name));
        $this->characterListAction->onReceive($user, '');
    }
}
