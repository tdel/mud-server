<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RoomRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RoomRepository $roomRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->roomRepository = self::getContainer()->get(RoomRepository::class);
    }

    public function testFindStartingRoomReturnsNullWhenNoneIsMarked(): void
    {
        $room = new Room('Ordinary room', 'Not the starting room.');
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        self::assertNull($this->roomRepository->findStartingRoom());
    }

    public function testFindStartingRoomReturnsTheMarkedRoom(): void
    {
        $room = new Room('Village square', 'The starting room.');
        $room->markAsStartingRoom();
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $found = $this->roomRepository->findStartingRoom();

        self::assertNotNull($found);
        self::assertSame($room->id->toString(), $found->id->toString());
    }

    public function testOnlyOneStartingRoomCanExist(): void
    {
        $first = new Room('First', 'The first starting room.');
        $first->markAsStartingRoom();
        $this->entityManager->persist($first);
        $this->entityManager->flush();

        $second = new Room('Second', 'A second starting room.');
        $second->markAsStartingRoom();
        $this->entityManager->persist($second);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
