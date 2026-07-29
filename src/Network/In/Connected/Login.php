<?php

namespace App\Network\In\Connected;

use App\Auth\AuthWorld;
use App\Auth\Client;
use App\Entity\Account;
use App\Game\GameWorld;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\In\Authed\CharacterList;
use App\Network\Out\Connected\AccountAlreadyConnected;
use App\Network\Out\Connected\AccountNotFound;
use App\Network\Out\Connected\IncorrectPassword;
use App\Network\Out\Connected\WelcomeBack;
use App\Network\Out\Usage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class Login extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthWorld $authWorld,
        private readonly GameWorld $gameWorld,
        private readonly CharacterList $charactersCommand,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function name(): string
    {
        return 'login';
    }

    public function states(): array
    {
        return [ConnectionState::Connected];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $client->send(new Usage('login <name>'));

            return;
        }

        $account = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);

        if ($account === null) {
            $client->send(new AccountNotFound($login));

            return;
        }

        $client->promptMasked('Password: ', function (string $password) use ($client, $account, $login): void {
            $this->onPasswordEntered($client, $account, $login, $password);
        });
    }

    private function onPasswordEntered(Client $client, Account $account, string $login, string $password): void
    {
        if (!$this->passwordHasher->isPasswordValid($account, $password)) {
            $client->send(new IncorrectPassword());

            return;
        }

        if ($this->authWorld->isAccountConnected($account) || $this->gameWorld->isAccountConnected($account)) {
            $client->send(new AccountAlreadyConnected($login));

            return;
        }

        $client->setAccount($account);
        $client->setPlayer(null);
        $client->setState(ConnectionState::Authed);

        $client->send(new WelcomeBack($login));
        $this->charactersCommand->onClientAction($client, '');
    }
}
