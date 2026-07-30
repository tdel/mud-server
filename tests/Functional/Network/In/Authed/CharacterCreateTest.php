<?php

namespace App\Tests\Functional\Network\In\Authed;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Room;
use App\Network\ConnectionState;
use App\Network\In\Authed\CharacterCreate;
use App\Network\Out\Authed\CharacterAlreadyExists;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\ChooseRace;
use App\Network\Out\Authed\InvalidRace;
use App\Network\Out\Authed\NoStartingRoom;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class CharacterCreateTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CharacterCreate $characterCreate;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->characterCreate = self::getContainer()->get(CharacterCreate::class);
    }

    public function testCreatingACharacterPersistsItInTheStartingRoomWithRacialBonuses(): void
    {
        $room = $this->persistStartingRoom();
        $user = $this->authedUser();

        $this->characterCreate->onReceive($user, 'Aldric');
        self::assertInstanceOf(ChooseRace::class, $user->lastSent());

        $user->answerAwaitedLine('dwarf');
        self::assertTrue($user->hasSent(CharacterCreated::class));

        $character = $this->entityManager->getRepository(Character::class)
            ->findOneBy(['account' => $user->account(), 'name' => 'Aldric']);

        self::assertNotNull($character);
        self::assertSame($room->id->toString(), $character->currentRoom->id->toString());

        // A roll of "4d6 drop the lowest" is always between 3 and 18; Dwarf
        // adds +2 to strength and constitution only, so — unlike the other
        // four abilities — those two can never end up below 5.
        self::assertGreaterThanOrEqual(5, $character->strength);
        self::assertLessThanOrEqual(20, $character->strength);
        self::assertGreaterThanOrEqual(5, $character->constitution);
        self::assertLessThanOrEqual(20, $character->constitution);
        self::assertGreaterThanOrEqual(3, $character->dexterity);
        self::assertLessThanOrEqual(18, $character->dexterity);
    }

    public function testInvalidRaceReprompts(): void
    {
        $this->persistStartingRoom();
        $user = $this->authedUser();

        $this->characterCreate->onReceive($user, 'Aldric');
        $user->answerAwaitedLine('not-a-real-race');

        self::assertTrue($user->hasSent(InvalidRace::class));

        $user->answerAwaitedLine('human');
        self::assertTrue($user->hasSent(CharacterCreated::class));
    }

    public function testDuplicateCharacterNameForSameAccountIsRejected(): void
    {
        $room = $this->persistStartingRoom();
        $user = $this->authedUser();

        $existing = new Character($user->account(), $room, 'Aldric', Race::Human, 100);
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $this->characterCreate->onReceive($user, 'Aldric');

        self::assertTrue($user->hasSent(CharacterAlreadyExists::class));
    }

    public function testNoStartingRoomIsReportedInsteadOfCrashing(): void
    {
        $user = $this->authedUser();

        $this->characterCreate->onReceive($user, 'Aldric');

        self::assertTrue($user->hasSent(NoStartingRoom::class));
    }

    private function persistStartingRoom(): Room
    {
        $room = new Room('Village square', 'The starting room.');
        $room->markAsStartingRoom();
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    private function authedUser(): InMemoryUser
    {
        $account = new Account('character-create-tester-'.Uuid::v7());
        $account->setPassword('irrelevant');
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $user = new InMemoryUser();
        $user->attachAccount($account);
        $user->setState(ConnectionState::Authed);

        return $user;
    }
}
