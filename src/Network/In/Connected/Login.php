<?php

namespace App\Network\In\Connected;

use App\Auth\AuthWorld;
use App\Entity\Account;
use App\Game\GameWorld;
use App\Network\In\Authed\CharacterList;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\AccountAlreadyConnected;
use App\Network\Out\AccountNotFound;
use App\Network\Out\Usage;
use App\Network\Out\WelcomeBack;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Login implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthWorld $authWorld,
        private readonly GameWorld $gameWorld,
        private readonly CharacterList $charactersCommand,
    ) {
    }

    public function name(): string
    {
        return 'login';
    }

    public function states(): array
    {
        return [TelnetState::Connected];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $session->client()->send(new Usage('login <name>'));

            return;
        }

        $account = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);

        if ($account === null) {
            $session->client()->send(new AccountNotFound($login));

            return;
        }

        if ($this->authWorld->isAccountConnected($account) || $this->gameWorld->isAccountConnected($account)) {
            $session->client()->send(new AccountAlreadyConnected($login));

            return;
        }

        $session->setAccount($account);
        $session->setPlayer(null);
        $session->setState(TelnetState::Authed);

        $session->client()->send(new WelcomeBack($login));
        $this->charactersCommand->execute($session, '');
    }
}
