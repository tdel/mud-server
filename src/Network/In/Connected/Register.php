<?php

namespace App\Network\In\Connected;

use App\Auth\Client;
use App\Entity\Account;
use App\Network\ConnectionState;
use App\Network\In\AbstractClientAction;
use App\Network\In\Authed\CharacterList;
use App\Network\Out\Connected\AccountCreated;
use App\Network\Out\Connected\InvalidPassword;
use App\Network\Out\Connected\LoginAlreadyTaken;
use App\Network\Out\Connected\PasswordMismatch;
use App\Network\Out\Usage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Register extends AbstractClientAction
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterList $charactersCommand,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function name(): string
    {
        return 'register';
    }

    public function states(): array
    {
        return [ConnectionState::Connected];
    }

    public function onClientAction(Client $client, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $client->send(new Usage('register <name>'));

            return;
        }

        $existing = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);
        if ($existing !== null) {
            $client->send(new LoginAlreadyTaken($login));

            return;
        }

        $client->promptMasked('Password: ', function (string $password) use ($client, $login): void {
            $this->onPasswordEntered($client, $login, $password);
        });
    }

    private function onPasswordEntered(Client $client, string $login, string $password): void
    {
        $violations = $this->validator->validate($password, [
            new Assert\NotBlank(),
            new Assert\Length(min: 8, max: 128),
        ]);

        if (count($violations) > 0) {
            $reasons = [];
            foreach ($violations as $violation) {
                $reasons[] = (string) $violation->getMessage();
            }

            $client->send(new InvalidPassword($reasons));

            return;
        }

        $client->promptMasked('Confirm password: ', function (string $confirmation) use ($client, $login, $password): void {
            $this->onPasswordConfirmed($client, $login, $password, $confirmation);
        });
    }

    private function onPasswordConfirmed(Client $client, string $login, string $password, string $confirmation): void
    {
        if ($password !== $confirmation) {
            $client->send(new PasswordMismatch());

            return;
        }

        $account = new Account($login);
        $account->setPassword($this->passwordHasher->hashPassword($account, $password));

        try {
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $client->send(new LoginAlreadyTaken($login));

            return;
        }

        $client->setAccount($account);
        $client->setPlayer(null);
        $client->setState(ConnectionState::Authed);

        $client->send(new AccountCreated($login));
        $this->charactersCommand->onClientAction($client, '');
    }
}
