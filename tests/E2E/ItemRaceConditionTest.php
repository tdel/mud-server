<?php

namespace App\Tests\E2E;

use App\Entity\Account;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Entity\Room;
use App\Network\ActionDispatcher;
use App\Network\Out\Ingame\ItemNotFound;
use App\Network\Out\Ingame\ItemTaken;
use App\Network\Out\Ingame\RoomDescription;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class ItemRaceConditionTest extends KernelTestCase
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

    public function testTwoPlayersTakeSameItemOnlyOneSucceeds(): void
    {
        $room = $this->persistStartingRoom();
        $template = new ItemTemplate(Uuid::v7(), 'Sword-' . Uuid::v7(), null, ItemType::Weapon, 3);
        $sword = new Item($template);
        $sword->moveToRoom($room);
        $this->em->persist($template);
        $this->em->persist($sword);
        $this->em->flush();

        $userA = $this->loginAndEnterGame('Alice');
        $userB = $this->loginAndEnterGame('Bob');

        // A takes the sword first
        $userA->sent = [];
        $this->dispatch($userA, 'take ' . $template->name);
        self::assertInstanceOf(ItemTaken::class, $userA->lastSent());

        // B tries to take the same sword — it's gone
        $userB->sent = [];
        $this->dispatch($userB, 'take ' . $template->name);
        self::assertInstanceOf(ItemNotFound::class, $userB->lastSent());

        // Verify in DB: item belongs to A's character
        $this->em->clear();
        $reloaded = $this->em->getRepository(Item::class)->find($sword->id);
        self::assertNotNull($reloaded);
        self::assertNotNull($reloaded->character);
        self::assertSame(
            $userA->account()->currentCharacter->id->toString(),
            $reloaded->character->id->toString(),
        );
    }

    public function testItemDisappearsFromRoomAfterTaken(): void
    {
        $room = $this->persistStartingRoom();
        $template = new ItemTemplate(Uuid::v7(), 'Shield-' . Uuid::v7(), null, ItemType::Armor, 5);
        $shield = new Item($template);
        $shield->moveToRoom($room);
        $this->em->persist($template);
        $this->em->persist($shield);
        $this->em->flush();

        $user = $this->loginAndEnterGame('Hero');

        // Look — item is visible
        $user->sent = [];
        $this->dispatch($user, 'look');
        $roomDesc = $user->lastSent();
        self::assertInstanceOf(RoomDescription::class, $roomDesc);

        // Take it
        $user->sent = [];
        $this->dispatch($user, 'take ' . $template->name);
        self::assertInstanceOf(ItemTaken::class, $user->lastSent());

        // Look again — item is gone
        $user->sent = [];
        $this->dispatch($user, 'look');
        self::assertInstanceOf(RoomDescription::class, $user->lastSent());

        // Verify via DB: no items in the room
        $itemsInRoom = $this->em->getRepository(Item::class)->findBy(['room' => $room]);
        self::assertCount(0, $itemsInRoom);
    }

    public function testDroppedItemVisibleToOtherPlayer(): void
    {
        $room = $this->persistStartingRoom();
        $template = new ItemTemplate(Uuid::v7(), 'Potion-' . Uuid::v7(), null, ItemType::Potion, 1);
        $this->em->persist($template);
        $this->em->flush();

        $userA = $this->loginAndEnterGame('Alice');
        $userB = $this->loginAndEnterGame('Bob');

        // Give item directly to A's character
        $potion = new Item($template);
        $potion->moveToCharacter($userA->account()->currentCharacter);
        $this->em->persist($potion);
        $this->em->flush();

        // B looks — no items in room
        $itemsInRoom = $this->em->getRepository(Item::class)->findBy(['room' => $room]);
        self::assertCount(0, $itemsInRoom);

        // A drops the item
        $this->dispatch($userA, 'drop ' . $template->name);

        // Item is now in the room
        $itemsInRoom = $this->em->getRepository(Item::class)->findBy(['room' => $room]);
        self::assertCount(1, $itemsInRoom);

        // B can take it
        $userB->sent = [];
        $this->dispatch($userB, 'take ' . $template->name);
        self::assertInstanceOf(ItemTaken::class, $userB->lastSent());
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

    private function loginAndEnterGame(string $characterName): InMemoryUser
    {
        $login = 'item-race-' . Uuid::v7();
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
}
