<?php

namespace App\Tests\E2E;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Room;
use App\Game\AuthWorld;
use App\Game\GameWorld;
use App\Network\ActionDispatcher;
use App\Network\ConnectionState;
use App\Network\Out\ActionNotFound;
use App\Network\Out\Connected\AccountAlreadyConnected;
use App\Network\Out\Authed\StoppedPlaying;
use App\Network\Out\LoggedOut;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class StateMachineBoundaryTest extends KernelTestCase
{
    private ActionDispatcher $dispatcher;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private AuthWorld $authWorld;
    private GameWorld $gameWorld;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $this->dispatcher = $c->get(ActionDispatcher::class);
        $this->em = $c->get(EntityManagerInterface::class);
        $this->hasher = $c->get(UserPasswordHasherInterface::class);
        $this->authWorld = $c->get(AuthWorld::class);
        $this->gameWorld = $c->get(GameWorld::class);
    }

    #[DataProvider('ingameCommandsProvider')]
    public function testIngameCommandsRejectedInConnectedState(string $command): void
    {
        $user = new InMemoryUser();

        $this->dispatch($user, $command);

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }

    #[DataProvider('ingameCommandsProvider')]
    public function testIngameCommandsRejectedInAuthedState(string $command): void
    {
        $user = $this->loginUser();

        $this->dispatch($user, $command);

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }

    #[DataProvider('authedCommandsProvider')]
    public function testAuthedCommandsRejectedInConnectedState(string $command): void
    {
        $user = new InMemoryUser();

        $this->dispatch($user, $command);

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }

    #[DataProvider('connectedCommandsProvider')]
    public function testConnectedCommandsRejectedInIngameState(string $command): void
    {
        $this->persistStartingRoom();
        $user = $this->loginAndEnterGame();

        $this->dispatch($user, $command);

        self::assertInstanceOf(ActionNotFound::class, $user->lastSent());
    }

    public function testLogoutWorksFromBothAuthedAndIngame(): void
    {
        $this->persistStartingRoom();
        $login = 'logout-test-' . Uuid::v7();
        $user = $this->loginUser($login);
        self::assertSame(ConnectionState::Authed, $user->state());

        $this->dispatch($user, 'logout');
        self::assertSame(ConnectionState::Connected, $user->state());
        self::assertTrue($user->hasSent(LoggedOut::class));

        $user->sent = [];
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('password');
        self::assertSame(ConnectionState::Authed, $user->state());

        $this->enterGame($user);
        self::assertSame(ConnectionState::Ingame, $user->state());

        $this->dispatch($user, 'logout');
        self::assertSame(ConnectionState::Authed, $user->state());
        self::assertTrue($user->hasSent(StoppedPlaying::class));
    }

    public function testDuplicateLoginPreventionFromAuthed(): void
    {
        $login = 'dupe-authed-' . Uuid::v7();
        $userA = $this->loginUser($login);
        self::assertSame(ConnectionState::Authed, $userA->state());

        $userB = new InMemoryUser();
        $this->dispatch($userB, 'login ' . $login);
        $userB->answerMaskedPrompt('password');

        self::assertTrue($userB->hasSent(AccountAlreadyConnected::class));
        self::assertSame(ConnectionState::Connected, $userB->state());
    }

    public function testDuplicateLoginPreventionFromIngame(): void
    {
        $this->persistStartingRoom();
        $login = 'dupe-ingame-' . Uuid::v7();
        $userA = $this->loginUser($login);
        $this->enterGame($userA);
        self::assertSame(ConnectionState::Ingame, $userA->state());

        $userB = new InMemoryUser();
        $this->dispatch($userB, 'login ' . $login);
        $userB->answerMaskedPrompt('password');

        self::assertTrue($userB->hasSent(AccountAlreadyConnected::class));
        self::assertSame(ConnectionState::Connected, $userB->state());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ingameCommandsProvider(): iterable
    {
        yield 'look' => ['look'];
        yield 'go' => ['go north'];
        yield 'say' => ['say hello'];
        yield 'take' => ['take sword'];
        yield 'drop' => ['drop sword'];
        yield 'inventory' => ['inventory'];
        yield 'equip' => ['equip sword'];
        yield 'unequip' => ['unequip sword'];
        yield 'examine' => ['examine someone'];
        yield 'roll' => ['roll 1d20'];
        yield 'stats' => ['stats'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function authedCommandsProvider(): iterable
    {
        yield 'character-create' => ['character-create Test'];
        yield 'character-select' => ['character-select Test'];
        yield 'character-delete' => ['character-delete Test'];
        yield 'characters-list' => ['characters-list'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function connectedCommandsProvider(): iterable
    {
        yield 'login' => ['login someone'];
        yield 'register' => ['register someone'];
        yield 'quit' => ['quit'];
    }

    private function dispatch(InMemoryUser $user, string $input): void
    {
        $parts = explode(' ', trim($input), 2);
        $this->dispatcher->dispatch($user, $parts[0], $parts[1] ?? '');
    }

    private function loginUser(?string $login = null): InMemoryUser
    {
        $login ??= 'state-test-' . Uuid::v7();
        $account = new Account($login);
        $account->setPassword($this->hasher->hashPassword($account, 'password'));
        $this->em->persist($account);
        $this->em->flush();

        $user = new InMemoryUser();
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('password');

        return $user;
    }

    private function persistStartingRoom(): Room
    {
        $room = new Room('Starting room', 'A test starting room.');
        $room->markAsStartingRoom();
        $this->em->persist($room);
        $this->em->flush();

        return $room;
    }

    private function enterGame(InMemoryUser $user, ?string $name = null): void
    {
        $name ??= 'Hero-' . Uuid::v7();
        $this->dispatch($user, 'character-create ' . $name);
        $user->answerAwaitedLine('human');
        $this->dispatch($user, 'character-select ' . $name);
    }

    private function loginAndEnterGame(): InMemoryUser
    {
        $user = $this->loginUser();
        $this->enterGame($user);

        return $user;
    }
}
