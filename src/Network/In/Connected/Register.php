<?php

namespace App\Network\In\Connected;

use App\Game\AuthWorld;
use App\Entity\Account;
use App\Network\ConnectionState;
use App\Network\In\ActionInterface;
use App\Network\In\Authed\CharacterList;
use App\Network\Out\Connected\AccountCreated;
use App\Network\Out\Connected\InvalidPassword;
use App\Network\Out\Connected\LoginAlreadyTaken;
use App\Network\Out\Connected\PasswordMismatch;
use App\Network\Out\Usage;
use App\Network\UserInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Register implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterList $characterListAction,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly AuthWorld $authWorld
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

    public function onReceive(UserInterface $user, string $argument): void
    {
        $login = trim($argument);

        if ($login === '') {
            $user->send(new Usage('register <name>'));

            return;
        }

        $existing = $this->entityManager->getRepository(Account::class)->findOneBy(['login' => $login]);
        if ($existing !== null) {
            $user->send(new LoginAlreadyTaken($login));

            return;
        }

        $user->promptMasked('Password: ', function (string $password) use ($user, $login): void {
            $this->onPasswordEntered($user, $login, $password);
        });
    }

    private function onPasswordEntered(UserInterface $user, string $login, string $password): void
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

            $user->send(new InvalidPassword($reasons));

            return;
        }

        $user->promptMasked('Confirm password: ', function (string $confirmation) use ($user, $login, $password): void {
            $this->onPasswordConfirmed($user, $login, $password, $confirmation);
        });
    }

    private function onPasswordConfirmed(UserInterface $user, string $login, string $password, string $confirmation): void
    {
        if ($password !== $confirmation) {
            $user->send(new PasswordMismatch());

            return;
        }

        $account = new Account($login);
        $account->setPassword($this->passwordHasher->hashPassword($account, $password));

        try {
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $user->send(new LoginAlreadyTaken($login));

            return;
        }

        $user->attachAccount($account);
        $this->authWorld->enterWorld($user);

        $user->send(new AccountCreated($login));
        $this->characterListAction->onReceive($user, '');
    }
}
