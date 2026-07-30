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

final class CharacterTest extends TestCase
{
    public function testEquipDelegatesToItemWhenOwned(): void
    {
        $character = $this->character();
        $item = new Item(new ItemTemplate(Uuid::v7(), 'Sword', null, ItemType::Weapon, 3));
        $item->moveToCharacter($character);

        $character->equip($item, EquipmentSlot::Weapon);

        self::assertSame(EquipmentSlot::Weapon, $item->slot);
    }

    public function testEquipThrowsWhenItemBelongsToAnotherCharacter(): void
    {
        $owner = $this->character();
        $someoneElse = $this->character();

        $item = new Item(new ItemTemplate(Uuid::v7(), 'Sword', null, ItemType::Weapon, 3));
        $item->moveToCharacter($owner);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item is not in this character\'s inventory.');

        $someoneElse->equip($item, EquipmentSlot::Weapon);
    }

    public function testEquipThrowsWhenItemIsNotCarriedByAnyone(): void
    {
        $character = $this->character();
        $item = new Item(new ItemTemplate(Uuid::v7(), 'Sword', null, ItemType::Weapon, 3));

        $this->expectException(\LogicException::class);

        $character->equip($item, EquipmentSlot::Weapon);
    }

    private function character(): Character
    {
        $room = new Room('Room', 'A room.');

        return new Character(new Account('tester'), $room, 'Hero', Race::Human, 10);
    }
}
