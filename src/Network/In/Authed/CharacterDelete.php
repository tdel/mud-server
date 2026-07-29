<?php

namespace App\Network\In\Authed;

use App\Entity\Character;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Authed\CharacterDeleted;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterDelete implements TelnetCommandInterface
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
        return [TelnetState::Authed];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->client()->send(new Usage('character-delete <name>'));
            $this->charactersCommand->execute($session, '');

            return;
        }

        $account = $session->account();
        $character = $this->entityManager->getRepository(Character::class)->findOneBy(['account' => $account, 'name' => $name]);

        if ($character === null) {
            $session->client()->send(new NoCharacterNamed($name));
            $this->charactersCommand->execute($session, '');

            return;
        }

        if ($account->currentCharacter !== null && $account->currentCharacter->id->equals($character->id)) {
            $account->clearCurrentCharacter();
            $this->entityManager->persist($account);
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        $session->client()->send(new CharacterDeleted($name));
        $this->charactersCommand->execute($session, '');
    }
}
