<?php

namespace App\Network\In\Connected;

use App\Game\AuthWorld;
use App\Entity\Account;
use App\Game\GameWorld;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\In\Authed\CharacterList;
use App\Network\Out\Connected\AccountAlreadyConnected;
use App\Network\Out\Connected\AccountNotFound;
use App\Network\Out\Connected\IncorrectPassword;
use App\Network\Out\Connected\WelcomeBack;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class Login implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthWorld $authWorld,
        private readonly GameWorld $gameWorld,
        private readonly CharacterList $characterListAction,
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

    #[\Override]
    public function onReceive(UserInterface $user, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $user->send(new Usage('login <name>'));

            return;
        }

        $account = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);

        if ($account === null) {
            $user->send(new AccountNotFound($login));

            return;
        }

        $user->promptMasked('Password: ', function (string $password) use ($user, $account, $login): void {
            $this->onPasswordEntered($user, $account, $login, $password);
        });

    }

    private function onPasswordEntered(UserInterface $user, Account $account, string $login, string $password): void
    {
        if (!$this->passwordHasher->isPasswordValid($account, $password)) {
            $user->send(new IncorrectPassword());

            return;
        }

        if ($this->authWorld->isAlreadyConnected($account) || $this->gameWorld->isAlreadyConnected($account)) {
            $user->send(new AccountAlreadyConnected($login));

            return;
        }

        $user->attachAccount($account);
        $this->authWorld->enterWorld($user);

        $user->send(new WelcomeBack($login));
        $this->characterListAction->onReceive($user, '');
    }


}
