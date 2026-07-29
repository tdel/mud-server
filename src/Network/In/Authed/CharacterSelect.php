<?php

namespace App\Network\In\Authed;

use App\Auth\AuthWorld;
use App\Entity\Character;
use App\Game\GameWorld;
use App\Game\Player;
use App\Network\In\Ingame\Look;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\Authed\NoCharacterNamed;
use App\Network\Out\Authed\NowPlaying;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class CharacterSelect implements TelnetCommandInterface
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
        return [TelnetState::Authed];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $name = trim($argument);

        if ($name === '') {
            $session->client()->send(new Usage('character-select <name>'));
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

        $account->setCurrentCharacter($character);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $player = new Player($character, $session->client());
        $this->authWorld->exitWorld($session->client());
        $this->gameWorld->enterWorld($player);

        $session->setPlayer($player);
        $session->setState(TelnetState::Ingame);

        $session->client()->send(new NowPlaying($character->name));
        $this->lookCommand->execute($session, '');
    }
}
