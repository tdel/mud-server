<?php

namespace App\Network\In\Connected;

use App\Entity\Account;
use App\Network\In\Authed\CharacterList;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\AccountCreated;
use App\Network\Out\LoginAlreadyTaken;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\ORM\EntityManagerInterface;

final class Register implements TelnetCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterList $charactersCommand,
    ) {
    }

    public function name(): string
    {
        return 'register';
    }

    public function states(): array
    {
        return [TelnetState::Connected];
    }

    public function execute(TelnetSession $session, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $session->client()->send(new Usage('register <name>'));

            return;
        }

        $existing = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);
        if ($existing !== null) {
            $session->client()->send(new LoginAlreadyTaken($login));

            return;
        }

        $account = new Account($login);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $session->setAccount($account);
        $session->setPlayer(null);
        $session->setState(TelnetState::Authed);

        $session->client()->send(new AccountCreated($login));
        $this->charactersCommand->execute($session, '');
    }
}
