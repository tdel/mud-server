<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\EquipmentSlot;
use App\Entity\Enum\Race;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Entity\Room;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ItemTest extends TestCase
{
    public function testMoveToRoomClearsCharacterAndSlot(): void
    {
        $item = $this->carriedAndEquippedItem();
        $room = new Room('Room', 'A room.');

        $item->moveToRoom($room);

        self::assertSame($room, $item->room);
        self::assertNull($item->character);
        self::assertNull($item->slot);
    }

    public function testMoveToCharacterClearsRoomAndSlot(): void
    {
        $room = new Room('Room', 'A room.');
        $item = new Item($this->weaponTemplate());
        $item->moveToRoom($room);

        $character = $this->character();
        $item->moveToCharacter($character);

        self::assertSame($character, $item->character);
        self::assertNull($item->room);
        self::assertNull($item->slot);
    }

    public function testEquipSucceedsWhenCarriedAndSlotMatchesType(): void
    {
        $character = $this->character();
        $item = new Item($this->weaponTemplate());
        $item->moveToCharacter($character);

        $item->equip(EquipmentSlot::Weapon);

        self::assertSame(EquipmentSlot::Weapon, $item->slot);
    }

    public function testEquipThrowsWhenNotCarried(): void
    {
        $item = new Item($this->weaponTemplate());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item must be carried by a character before it can be equipped.');

        $item->equip(EquipmentSlot::Weapon);
    }

    public function testEquipThrowsWhenSlotDoesNotMatchItemType(): void
    {
        $item = new Item($this->weaponTemplate());
        $item->moveToCharacter($this->character());

        $this->expectException(\LogicException::class);

        $item->equip(EquipmentSlot::Head);
    }

    public function testUnequipAlwaysSucceeds(): void
    {
        $item = $this->carriedAndEquippedItem();

        $item->unequip();

        self::assertNull($item->slot);
    }

    private function weaponTemplate(): ItemTemplate
    {
        return new ItemTemplate(Uuid::v7(), 'Sword', null, ItemType::Weapon, 3);
    }

    private function character(): Character
    {
        $room = new Room('Room', 'A room.');

        return new Character(new Account('tester'), $room, 'Hero', Race::Human, 10);
    }

    private function carriedAndEquippedItem(): Item
    {
        $item = new Item($this->weaponTemplate());
        $item->moveToCharacter($this->character());
        $item->equip(EquipmentSlot::Weapon);

        return $item;
    }
}
