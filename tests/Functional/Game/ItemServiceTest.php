<?php

namespace App\Tests\Functional\Game;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Entity\Room;
use App\Game\ItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ItemServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ItemService $itemService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->itemService = self::getContainer()->get(ItemService::class);
    }

    public function testAddItemToInventoryIsReflectedByASubsequentRead(): void
    {
        [$character, $room] = $this->persistCharacterInRoom();
        $sword = $this->persistItem('Sword', ItemType::Weapon, $room);

        // Regression guard: reading the inventory once *before* the mutation
        // used to poison an in-memory Doctrine collection under the old
        // ItemService implementation (it read Character::$items directly),
        // so that a later read never saw items added afterwards.
        self::assertSame([], $this->itemService->getInventory($character));

        self::assertTrue($this->itemService->addItemToInventory($sword, $character));

        $inventory = $this->itemService->getInventory($character);
        self::assertCount(1, $inventory);
        self::assertSame($sword->id->toString(), $inventory[0]->id->toString());
    }

    public function testAddItemToInventoryFailsIfAlreadyTaken(): void
    {
        [$firstCharacter, $room] = $this->persistCharacterInRoom();
        $secondAccount = new Account('item-service-tester-'.Uuid::v7());
        $secondAccount->setPassword('irrelevant');
        $secondCharacter = new Character($secondAccount, $room, 'Rival', Race::Human, 10);
        $this->entityManager->persist($secondAccount);
        $this->entityManager->persist($secondCharacter);
        $this->entityManager->flush();

        $sword = $this->persistItem('Sword', ItemType::Weapon, $room);

        // Simulates what a genuine race under Swoole coroutines produces:
        // by the time the second player's addItemToInventory() call
        // re-reads the row under lock, the first player already has it.
        self::assertTrue($this->itemService->addItemToInventory($sword, $firstCharacter));
        self::assertFalse($this->itemService->addItemToInventory($sword, $secondCharacter));

        self::assertNotNull($sword->character);
        self::assertSame($firstCharacter->id->toString(), $sword->character->id->toString());
    }

    public function testEquipItemReturnsNullForNonEquippableType(): void
    {
        [$character, $room] = $this->persistCharacterInRoom();
        $potion = $this->persistItem('Potion', ItemType::Potion, $room);
        $this->itemService->addItemToInventory($potion, $character);

        self::assertNull($this->itemService->equipItem($potion, $character));
        self::assertNull($potion->slot);
    }

    public function testEquippingASecondItemInTheSameSlotUnequipsTheFirst(): void
    {
        [$character, $room] = $this->persistCharacterInRoom();
        $dagger = $this->persistItem('Dagger', ItemType::Weapon, $room);
        $sword = $this->persistItem('Sword', ItemType::Weapon, $room);
        $this->itemService->addItemToInventory($dagger, $character);
        $this->itemService->addItemToInventory($sword, $character);

        $this->itemService->equipItem($dagger, $character);
        $this->itemService->equipItem($sword, $character);

        self::assertNull($dagger->slot);
        self::assertNotNull($sword->slot);

        $equipped = $this->itemService->getEquippedItems($character);
        self::assertCount(1, $equipped);
        self::assertSame($sword->id->toString(), $equipped[0]->id->toString());
    }

    public function testRemoveItemFromInventoryDropsItInTheCharactersCurrentRoom(): void
    {
        [$character, $room] = $this->persistCharacterInRoom();
        $sword = $this->persistItem('Sword', ItemType::Weapon, $room);
        $this->itemService->addItemToInventory($sword, $character);

        $this->itemService->removeItemFromInventory($sword, $character);

        self::assertNull($sword->character);
        self::assertNotNull($sword->room);
        self::assertSame($room->id->toString(), $sword->room->id->toString());
        self::assertSame([], $this->itemService->getInventory($character));
    }

    /**
     * @return array{Character, Room}
     */
    private function persistCharacterInRoom(): array
    {
        $room = new Room('Test room', 'A room used for testing.');
        $account = new Account('item-service-tester-'.Uuid::v7());
        $account->setPassword('irrelevant');
        $character = new Character($account, $room, 'Hero', Race::Human, 10);

        $this->entityManager->persist($room);
        $this->entityManager->persist($account);
        $this->entityManager->persist($character);
        $this->entityManager->flush();

        return [$character, $room];
    }

    private function persistItem(string $name, ItemType $type, Room $room): Item
    {
        $template = new ItemTemplate(Uuid::v7(), $name.'-'.Uuid::v7(), null, $type, 1);
        $item = new Item($template);
        $item->moveToRoom($room);

        $this->entityManager->persist($template);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
