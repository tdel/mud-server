<?php

namespace App\Game;

use App\Entity\Character;
use App\Entity\Enum\EquipmentSlot;
use App\Entity\Item;
use App\Entity\Room;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Single entry point for reading and mutating a character's items — the
 * bag and the equipped slots alike. Owns Doctrine and flushes on every
 * mutation, so Actions never need to touch EntityManagerInterface or
 * repositories directly for item concerns.
 */
final class ItemService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Always queries fresh rather than reading Character::$items /
     * Room::$items directly: the telnet server keeps a single
     * EntityManager alive for its whole process lifetime, so once one
     * of those mapped collections is lazy-initialized it never
     * reflects a later change made only on the owning side (e.g.
     * Item::moveToCharacter()) — the inverse collection isn't
     * resynchronized. A fresh repository query sidesteps that.
     *
     * @return list<Item>
     */
    public function getInventory(Character $character): array
    {
        return $this->entityManager->getRepository(Item::class)->findBy(['character' => $character]);
    }

    /**
     * @return list<Item>
     */
    public function getCarriedItems(Character $character): array
    {
        return array_values(array_filter(
            $this->getInventory($character),
            static fn (Item $item): bool => $item->slot === null,
        ));
    }

    /**
     * @return list<Item>
     */
    public function getEquippedItems(Character $character): array
    {
        return array_values(array_filter(
            $this->getInventory($character),
            static fn (Item $item): bool => $item->slot !== null,
        ));
    }

    public function findItemByName(Character $character, string $name): ?Item
    {
        foreach ($this->getInventory($character) as $item) {
            if (strcasecmp($item->template->name, $name) === 0) {
                return $item;
            }
        }

        return null;
    }

    public function findItemInRoomByName(Room $room, string $name): ?Item
    {
        $items = $this->entityManager->getRepository(Item::class)->findBy(['room' => $room]);

        foreach ($items as $item) {
            if (strcasecmp($item->template->name, $name) === 0) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Takes $item into $target's bag, unless another coroutine already
     * claimed it since the caller looked it up. Concurrent players can
     * genuinely race to take the same unowned item under Swoole (they
     * never could under the previous single-threaded ReactPHP transport),
     * so this re-reads the row under a pessimistic write lock — inside a
     * transaction, so the lock is held until commit — before deciding
     * whether it's still there to take. No extra column needed: unlike
     * optimistic locking, PESSIMISTIC_WRITE (`SELECT ... FOR UPDATE`)
     * needs no #[ORM\Version] field, just an active transaction.
     *
     * @return bool true if $target now carries the item, false if it was
     *              already taken by someone else in the meantime
     */
    public function addItemToInventory(Item $item, Character $target): bool
    {
        return $this->entityManager->wrapInTransaction(function () use ($item, $target): bool {
            // find() with a lock mode always hits the database and
            // refreshes the (already-managed) entity's fields from that
            // locked read — unlike lock() alone, which only acquires the
            // lock without re-reading the row's current data.
            $this->entityManager->find(Item::class, $item->id, LockMode::PESSIMISTIC_WRITE);

            if ($item->character !== null) {
                return false;
            }

            $item->moveToCharacter($target);

            return true;
        });
    }

    public function removeItemFromInventory(Item $item, Character $target): void
    {
        $item->moveToRoom($target->currentRoom);
        $this->entityManager->flush();
    }

    public function equipItem(Item $item, Character $target): ?EquipmentSlot
    {
        $slot = $item->template->type->equipmentSlot();

        if ($slot === null) {
            return null;
        }

        foreach ($this->getEquippedItems($target) as $existing) {
            if ($existing !== $item && $existing->slot === $slot) {
                $existing->unequip();
            }
        }

        $target->equip($item, $slot);
        $this->entityManager->flush();

        return $slot;
    }

    public function unequipItem(Item $item): void
    {
        $item->unequip();
        $this->entityManager->flush();
    }
}
