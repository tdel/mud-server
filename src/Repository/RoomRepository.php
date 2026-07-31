<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Plain EntityManagerInterface-backed repository — deliberately not a
 * ServiceEntityRepository. Doctrine bundle's ContainerRepositoryFactory
 * resolves a ServiceEntityRepository straight from the container,
 * ignoring whichever EntityManager it was asked for, which would silently
 * defeat the per-coroutine EntityManager scoping the telnet server relies
 * on (see App\Doctrine\EntityManagerProxy).
 */
final class RoomRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findStartingRoom(): ?Room
    {
        return $this->entityManager->getRepository(Room::class)->findOneBy(['isStartingRoom' => true]);
    }

    public function findOneByName(string $name): ?Room
    {
        return $this->entityManager->getRepository(Room::class)->findOneBy(['name' => $name]);
    }

    /**
     * @return list<Room>
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Room::class)->findAll();
    }
}
