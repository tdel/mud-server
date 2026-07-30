<?php

namespace App\Tests\Functional\Network\In\Ingame;

use App\Entity\Account;
use App\Entity\Character;
use App\Entity\Enum\Race;
use App\Entity\Item;
use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Entity\Room;
use App\Game\PlayerInstance;
use App\Network\ConnectionState;
use App\Network\In\Ingame\Drop;
use App\Network\In\Ingame\Equip;
use App\Network\In\Ingame\Take;
use App\Network\In\Ingame\Unequip;
use App\Network\Out\Ingame\ItemDropped;
use App\Network\Out\Ingame\ItemEquipped;
use App\Network\Out\Ingame\ItemTaken;
use App\Network\Out\Ingame\ItemUnequipped;
use App\Tests\Support\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Replays, as an automated regression test, the take/equip/unequip/drop
 * flow that was manually verified over telnet against a real server.
 */
final class InventoryFlowTest extends KernelTestCase
{
    public function testTakeEquipUnequipDrop(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $room = new Room('Test room', 'A room used for testing.');
        $account = new Account('flow-tester-'.Uuid::v7());
        $account->setPassword('irrelevant');
        $character = new Character($account, $room, 'Hero', Race::Human, 10);
        $template = new ItemTemplate(Uuid::v7(), 'Sword-'.Uuid::v7(), null, ItemType::Weapon, 3);
        $sword = new Item($template);
        $sword->moveToRoom($room);

        $entityManager->persist($room);
        $entityManager->persist($account);
        $entityManager->persist($character);
        $entityManager->persist($template);
        $entityManager->persist($sword);
        $entityManager->flush();

        $account->setCurrentCharacter($character);

        $user = new InMemoryUser();
        $user->attachAccount($account);
        $user->setState(ConnectionState::Ingame);
        new PlayerInstance($user);

        $container->get(Take::class)->onReceive($user, $template->name);
        self::assertInstanceOf(ItemTaken::class, $user->lastSent());

        $container->get(Equip::class)->onReceive($user, $template->name);
        self::assertInstanceOf(ItemEquipped::class, $user->lastSent());

        $container->get(Unequip::class)->onReceive($user, $template->name);
        self::assertInstanceOf(ItemUnequipped::class, $user->lastSent());

        $container->get(Drop::class)->onReceive($user, $template->name);
        self::assertInstanceOf(ItemDropped::class, $user->lastSent());

        // Force a fresh read from the database, sidestepping any in-memory
        // identity map/collection state, to confirm the final position was
        // actually persisted correctly.
        $entityManager->clear();
        $reloaded = $entityManager->getRepository(Item::class)->find($sword->id);

        self::assertNotNull($reloaded);
        self::assertNull($reloaded->character);
        self::assertNotNull($reloaded->room);
        self::assertSame($room->id->toString(), $reloaded->room->id->toString());
    }
}
