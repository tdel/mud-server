<?php

namespace App\Tests\E2E;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Entity\Room;
use App\Entity\RoomExit;
use App\Network\ActionDispatcher;
use App\Network\ConnectionState;
use App\Network\Out\Authed\CharacterCreated;
use App\Network\Out\Authed\CharacterDeleted;
use App\Network\Out\Authed\NowPlaying;
use App\Network\Out\Authed\StoppedPlaying;
use App\Network\Out\Connected\AccountCreated;
use App\Network\Out\Connected\IncorrectPassword;
use App\Network\Out\Connected\PasswordMismatch;
use App\Network\Out\Connected\WelcomeBack;
use App\Network\Out\Ingame\CharacterStats;
use App\Network\Out\Ingame\DiceRolled;
use App\Network\Out\Ingame\ItemDropped;
use App\Network\Out\Ingame\ItemEquipped;
use App\Network\Out\Ingame\ItemTaken;
use App\Network\Out\Ingame\ItemUnequipped;
use App\Network\Out\Ingame\Inventory as InventoryMessage;
use App\Network\Out\Ingame\NoSuchExit;
use App\Network\Out\Ingame\RoomDescription;
use App\Network\Out\LoggedOut;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class FullLifecycleTest extends KernelTestCase
{
    private ActionDispatcher $dispatcher;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $this->dispatcher = $c->get(ActionDispatcher::class);
        $this->em = $c->get(EntityManagerInterface::class);
        $this->hasher = $c->get(UserPasswordHasherInterface::class);
    }

    public function testRegisterCreateCharacterPlayAndLogout(): void
    {
        $this->persistStartingRoom();
        $login = 'lifecycle-' . Uuid::v7();
        $user = new InMemoryUser();

        // Register
        $this->dispatch($user, 'register ' . $login);
        $user->answerMaskedPrompt('secret1234');
        $user->answerMaskedPrompt('secret1234');
        self::assertTrue($user->hasSent(AccountCreated::class));
        self::assertSame(ConnectionState::Authed, $user->state());

        // Create character
        $this->dispatch($user, 'character-create Aldric');
        $user->answerAwaitedLine('dwarf');
        self::assertTrue($user->hasSent(CharacterCreated::class));

        $character = $this->em->getRepository(Character::class)
            ->findOneBy(['account' => $user->account(), 'name' => 'Aldric']);
        self::assertNotNull($character);
        self::assertSame(Race::Dwarf, $character->race);

        // Select character → enter game
        $user->sent = [];
        $this->dispatch($user, 'character-select Aldric');
        self::assertTrue($user->hasSent(NowPlaying::class));
        self::assertTrue($user->hasSent(RoomDescription::class));
        self::assertSame(ConnectionState::Ingame, $user->state());

        // In-game commands
        $user->sent = [];
        $this->dispatch($user, 'look');
        self::assertInstanceOf(RoomDescription::class, $user->lastSent());

        $user->sent = [];
        $this->dispatch($user, 'stats');
        self::assertInstanceOf(CharacterStats::class, $user->lastSent());

        $user->sent = [];
        $this->dispatch($user, 'roll 2d6+3');
        self::assertInstanceOf(DiceRolled::class, $user->lastSent());

        // Logout from ingame → authed
        $user->sent = [];
        $this->dispatch($user, 'logout');
        self::assertTrue($user->hasSent(StoppedPlaying::class));
        self::assertSame(ConnectionState::Authed, $user->state());

        // Logout from authed → connected
        $user->sent = [];
        $this->dispatch($user, 'logout');
        self::assertInstanceOf(LoggedOut::class, $user->lastSent());
        self::assertSame(ConnectionState::Connected, $user->state());
    }

    public function testLoginExistingAccountAndSelectCharacter(): void
    {
        $room = $this->persistStartingRoom();
        $login = 'existing-' . Uuid::v7();
        $account = new Account($login);
        $account->setPassword($this->hasher->hashPassword($account, 'mypassword'));
        $character = new Character($account, $room, 'Thorin', Race::Dwarf, 100);
        $this->em->persist($account);
        $this->em->persist($character);
        $this->em->flush();

        $user = new InMemoryUser();
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('mypassword');
        self::assertTrue($user->hasSent(WelcomeBack::class));
        self::assertSame(ConnectionState::Authed, $user->state());

        $user->sent = [];
        $this->dispatch($user, 'character-select Thorin');
        self::assertTrue($user->hasSent(NowPlaying::class));
        self::assertTrue($user->hasSent(RoomDescription::class));
        self::assertSame(ConnectionState::Ingame, $user->state());
    }

    public function testRegisterPasswordMismatch(): void
    {
        $login = 'mismatch-' . Uuid::v7();
        $user = new InMemoryUser();

        $this->dispatch($user, 'register ' . $login);
        $user->answerMaskedPrompt('password123');
        $user->answerMaskedPrompt('different456');

        self::assertTrue($user->hasSent(PasswordMismatch::class));
        self::assertSame(ConnectionState::Connected, $user->state());

        $account = $this->em->getRepository(Account::class)->findOneBy(['login' => $login]);
        self::assertNull($account);
    }

    public function testLoginWrongPassword(): void
    {
        $login = 'wrongpass-' . Uuid::v7();
        $account = new Account($login);
        $account->setPassword($this->hasher->hashPassword($account, 'correct'));
        $this->em->persist($account);
        $this->em->flush();

        $user = new InMemoryUser();
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('wrong');

        self::assertTrue($user->hasSent(IncorrectPassword::class));
        self::assertSame(ConnectionState::Connected, $user->state());
    }

    public function testCharacterDeleteAndRecreate(): void
    {
        $this->persistStartingRoom();
        $user = $this->loginUser();

        // Create as dwarf
        $this->dispatch($user, 'character-create Aldric');
        $user->answerAwaitedLine('dwarf');
        self::assertTrue($user->hasSent(CharacterCreated::class));

        // Delete
        $user->sent = [];
        $this->dispatch($user, 'character-delete Aldric');
        self::assertTrue($user->hasSent(CharacterDeleted::class));

        $deleted = $this->em->getRepository(Character::class)
            ->findOneBy(['account' => $user->account(), 'name' => 'Aldric']);
        self::assertNull($deleted);

        // Recreate as human
        $user->sent = [];
        $this->dispatch($user, 'character-create Aldric');
        $user->answerAwaitedLine('human');
        self::assertTrue($user->hasSent(CharacterCreated::class));

        $recreated = $this->em->getRepository(Character::class)
            ->findOneBy(['account' => $user->account(), 'name' => 'Aldric']);
        self::assertNotNull($recreated);
        self::assertSame(Race::Human, $recreated->race);
    }

    public function testNavigationBetweenRooms(): void
    {
        $room1 = $this->persistStartingRoom();
        $room2 = new Room('Dark forest', 'A spooky forest.');
        $this->em->persist($room2);
        $this->em->flush();

        $this->createExit($room1, $room2, 'north');
        $this->createExit($room2, $room1, 'south');

        $user = $this->loginUser();
        $this->enterGame($user);

        // Go north
        $user->sent = [];
        $this->dispatch($user, 'go north');
        self::assertTrue($user->hasSent(RoomDescription::class));
        self::assertSame($room2->id->toString(), $user->player()->currentRoom()->id->toString());

        // Go south
        $user->sent = [];
        $this->dispatch($user, 'go south');
        self::assertTrue($user->hasSent(RoomDescription::class));
        self::assertSame($room1->id->toString(), $user->player()->currentRoom()->id->toString());

        // Invalid direction
        $user->sent = [];
        $this->dispatch($user, 'go west');
        self::assertInstanceOf(NoSuchExit::class, $user->lastSent());
    }

    public function testInventoryFullCycleThroughDispatcher(): void
    {
        $room = $this->persistStartingRoom();
        $template = new ItemTemplate(Uuid::v7(), 'Sword-' . Uuid::v7(), null, ItemType::Weapon, 3);
        $sword = new Item($template);
        $sword->moveToRoom($room);
        $this->em->persist($template);
        $this->em->persist($sword);
        $this->em->flush();

        $user = $this->loginUser();
        $this->enterGame($user);

        // Take
        $user->sent = [];
        $this->dispatch($user, 'take ' . $template->name);
        self::assertInstanceOf(ItemTaken::class, $user->lastSent());

        // Inventory shows the item
        $user->sent = [];
        $this->dispatch($user, 'inventory');
        self::assertInstanceOf(InventoryMessage::class, $user->lastSent());

        // Equip
        $user->sent = [];
        $this->dispatch($user, 'equip ' . $template->name);
        self::assertInstanceOf(ItemEquipped::class, $user->lastSent());

        // Unequip
        $user->sent = [];
        $this->dispatch($user, 'unequip ' . $template->name);
        self::assertInstanceOf(ItemUnequipped::class, $user->lastSent());

        // Drop
        $user->sent = [];
        $this->dispatch($user, 'drop ' . $template->name);
        self::assertInstanceOf(ItemDropped::class, $user->lastSent());

        // Verify item is back in the room
        $this->em->clear();
        $reloaded = $this->em->getRepository(Item::class)->find($sword->id);
        self::assertNotNull($reloaded);
        self::assertNull($reloaded->character);
        self::assertNotNull($reloaded->room);
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

    private function loginUser(): InMemoryUser
    {
        $login = 'lifecycle-' . Uuid::v7();
        $account = new Account($login);
        $account->setPassword($this->hasher->hashPassword($account, 'password'));
        $this->em->persist($account);
        $this->em->flush();

        $user = new InMemoryUser();
        $this->dispatch($user, 'login ' . $login);
        $user->answerMaskedPrompt('password');

        return $user;
    }

    private function enterGame(InMemoryUser $user, ?string $name = null): void
    {
        $name ??= 'Hero-' . Uuid::v7();
        $this->dispatch($user, 'character-create ' . $name);
        $user->answerAwaitedLine('human');
        $this->dispatch($user, 'character-select ' . $name);
    }
}
