<?php

namespace App\Network\In\Authed;

use App\Entity\Character;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Authed\CharacterList as CharacterListMessage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterList implements TelnetCommandInterface
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
        return [TelnetState::Authed];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $characters = $this->entityManager->getRepository(Character::class)->findBy(['account' => $session->account()]);

        $names = array_map(static fn (Character $character): string => $character->name, $characters);

        $session->client()->send(new CharacterListMessage($names));
    }
}
