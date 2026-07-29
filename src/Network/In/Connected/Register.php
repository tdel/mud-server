<?php

namespace App\Network\In\Connected;

use App\Entity\Account;
use App\Network\In\Authed\CharacterList;
use App\Network\In\TelnetCommandInterface;
use App\Network\Out\AccountCreated;
use App\Network\Out\InvalidPassword;
use App\Network\Out\LoginAlreadyTaken;
use App\Network\Out\PasswordMismatch;
use App\Network\Out\Usage;
use App\Network\Telnet\TelnetSession;
use App\Network\Telnet\TelnetState;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Register implements TelnetCommandInterface
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

        $session->promptMasked('Password: ', function (string $password) use ($session, $login): void {
            $this->onPasswordEntered($session, $login, $password);
        });
    }

    private function onPasswordEntered(TelnetSession $session, string $login, string $password): void
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

            $session->client()->send(new InvalidPassword($reasons));

            return;
        }

        $session->promptMasked('Confirm password: ', function (string $confirmation) use ($session, $login, $password): void {
            $this->onPasswordConfirmed($session, $login, $password, $confirmation);
        });
    }

    private function onPasswordConfirmed(TelnetSession $session, string $login, string $password, string $confirmation): void
    {
        if ($password !== $confirmation) {
            $session->client()->send(new PasswordMismatch());

            return;
        }

        $account = new Account($login);
        $account->setPassword($this->passwordHasher->hashPassword($account, $password));

        try {
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $session->client()->send(new LoginAlreadyTaken($login));

            return;
        }

        $session->setAccount($account);
        $session->setPlayer(null);
        $session->setState(TelnetState::Authed);

        $session->client()->send(new AccountCreated($login));
        $this->charactersCommand->execute($session, '');
    }
}
