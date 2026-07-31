<?php

namespace App\Tests\E2E;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\RoomExit;
use App\Game\GameWorld;
use App\Network\ActionDispatcher;
use App\Network\ConnectionState;
use App\Network\Out\Authed\StoppedPlaying;
use App\Network\Out\Ingame\CharacterDisconnected;
use App\Network\Out\Ingame\CharacterJoinedRoom;
use App\Network\Out\Ingame\CharacterLeftRoom;
use App\Network\Out\Ingame\CharacterNotFound;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Ingame\Chat;
use App\Network\Out\Ingame\RoomDescription;
use App\Network\Out\Ingame\YouSaid;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class MultiPlayerInteractionTest extends KernelTestCase
{
    private ActionDispatcher $dispatcher;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private GameWorld $gameWorld;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $this->dispatcher = $c->get(ActionDispatcher::class);
        $this->em = $c->get(EntityManagerInterface::class);
        $this->hasher = $c->get(UserPasswordHasherInterface::class);
        $this->gameWorld = $c->get(GameWorld::class);
    }

    public function testTwoPlayersInSameRoomSeeEachOther(): void
    {
        $room = $this->persistStartingRoom();
        [$userA, $userB] = $this->twoPlayersInGame('Lyra', 'Kael');

        $characters = $this->gameWorld->roomInstance($room)->characters();
        $names = array_map(fn ($c) => $c->name, $characters);

        self::assertContains('Lyra', $names);
        self::assertContains('Kael', $names);

        $userA->sent = [];
        $this->dispatch($userA, 'look');
        self::assertInstanceOf(RoomDescription::class, $userA->lastSent());

        $userB->sent = [];
        $this->dispatch($userB, 'look');
        self::assertInstanceOf(RoomDescription::class, $userB->lastSent());
    }

    public function testSayBroadcastsToRoommates(): void
    {
        $this->persistStartingRoom();
        [$userA, $userB] = $this->twoPlayersInGame('Lyra', 'Kael');

        $userA->sent = [];
        $userB->sent = [];
        $this->dispatch($userA, 'say Bonjour le monde');

        self::assertTrue($userA->hasSent(YouSaid::class));
        self::assertTrue($userB->hasSent(Chat::class));
    }

    public function testMovementBroadcastsLeaveAndJoin(): void
    {
        $room1 = $this->persistStartingRoom();
        $room2 = new Room('Dark forest', 'A spooky forest.');
        $this->em->persist($room2);
        $this->em->flush();
        $this->createExit($room1, $room2, 'north');
        $this->createExit($room2, $room1, 'south');

        [$userA, $userB] = $this->twoPlayersInGame('Lyra', 'Kael');

        // A goes north — B should see the departure
        $userB->sent = [];
        $this->dispatch($userA, 'go north');

        self::assertTrue($userB->hasSent(CharacterLeftRoom::class));

        // B follows — A should see the arrival
        $userA->sent = [];
        $this->dispatch($userB, 'go north');

        self::assertTrue($userA->hasSent(CharacterJoinedRoom::class));

        // Both see each other
        $characters = $this->gameWorld->roomInstance($room2)->characters();
        $names = array_map(fn ($c) => $c->name, $characters);
        self::assertContains('Lyra', $names);
        self::assertContains('Kael', $names);
    }

    public function testExamineAnotherPlayer(): void
    {
        $this->persistStartingRoom();
        [$userA, $userB] = $this->twoPlayersInGame('Lyra', 'Kael');

        $userA->sent = [];
        $this->dispatch($userA, 'examine Kael');

        self::assertInstanceOf(CharacterStats::class, $userA->lastSent());
    }

    public function testExamineNonexistentCharacter(): void
    {
        $this->persistStartingRoom();
        $userA = $this->loginAndEnterGame('Lyra');

        $userA->sent = [];
        $this->dispatch($userA, 'examine Ghost');

        self::assertInstanceOf(CharacterNotFound::class, $userA->lastSent());
    }

    public function testLogoutBroadcastsDisconnect(): void
    {
        $this->persistStartingRoom();
        [$userA, $userB] = $this->twoPlayersInGame('Lyra', 'Kael');

        $userB->sent = [];
        $this->dispatch($userA, 'logout');

        self::assertTrue($userA->hasSent(StoppedPlaying::class));
        self::assertSame(ConnectionState::Authed, $userA->state());
        self::assertTrue($userB->hasSent(CharacterDisconnected::class));
    }

    public function testSayAloneInRoom(): void
    {
        $this->persistStartingRoom();
        $user = $this->loginAndEnterGame('Lyra');

        $user->sent = [];
        $this->dispatch($user, 'say Hello');

        self::assertInstanceOf(YouSaid::class, $user->lastSent());
    }

    private function dispatch(InMemoryUser $user, string $input): void
    {
        $parts = explode(' ', trim($input), 2);
        $this->dispatcher->dispatch($user, $parts[0], $parts[1] ?? '');
    }

    private function persistStartingRoom(): Room
    {
        $room = new Room('Village square', 'The starting room.');
        $room->markAsStartingRoom();
        $this->em->persist($room);
        $this->em->flush();

        return $room;
    }

    private function createExit(Room $source, Room $target, string $direction): void
    {
        $ref = new \ReflectionClass(RoomExit::class);
        $exit = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('sourceRoom')->setValue($exit, $source);
        $ref->getProperty('targetRoom')->setValue($exit, $target);
        $ref->getProperty('direction')->setValue($exit, $direction);

        $this->em->persist($exit);
        $this->em->flush();
    }

    private function loginAndEnterGame(string $characterName): InMemoryUser
    {
        $login = 'mp-' . Uuid::v7();
        $account = new Account($login);
        $account->setPassword($this->hasher->hashPassword($account, 'password'));
        $this->em->persist($account);
        $this->em->flush();

        $user = new InMemoryUser();
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('password');
        $this->dispatch($user, 'character-create ' . $characterName);
        $user->answerAwaitedLine('human');
        $this->dispatch($user, 'character-select ' . $characterName);

        return $user;
    }

    /**
     * @return array{InMemoryUser, InMemoryUser}
     */
    private function twoPlayersInGame(string $nameA, string $nameB): array
    {
        return [
            $this->loginAndEnterGame($nameA),
            $this->loginAndEnterGame($nameB),
        ];
    }
}
