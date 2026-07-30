<?php

namespace App\Network\In\Authed;

use App\Entity\Character;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\Out\Authed\CharacterList as CharacterListMessage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterList implements ActionInterface
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $account = $user->account();
        $characters = $this->entityManager->getRepository(Character::class)->findBy(['account' => $account]);

        $names = array_map(static fn (Character $character): string => $character->name, $characters);

        $user->send(new CharacterListMessage($names));
    }
}
