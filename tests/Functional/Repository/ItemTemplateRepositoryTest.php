<?php

namespace App\Tests\Functional\Repository;

use App\Entity\ItemTemplate;
use App\Entity\ItemType;
use App\Repository\ItemTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ItemTemplateRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ItemTemplateRepository $itemTemplateRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->itemTemplateRepository = self::getContainer()->get(ItemTemplateRepository::class);
    }

    public function testFindOneByNameFindsAnExactMatch(): void
    {
        $template = new ItemTemplate(Uuid::v7(), 'Rusty Dagger', null, ItemType::Weapon, 2);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $found = $this->itemTemplateRepository->findOneByName('Rusty Dagger');

        self::assertNotNull($found);
        self::assertSame($template->id->toString(), $found->id->toString());
    }

    public function testFindOneByNameReturnsNullForAnUnknownName(): void
    {
        self::assertNull($this->itemTemplateRepository->findOneByName('Does Not Exist'));
    }

    public function testFindOneByNameIsCaseSensitive(): void
    {
        $template = new ItemTemplate(Uuid::v7(), 'Rusty Dagger', null, ItemType::Weapon, 2);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        // Unlike ItemService's application-level case-insensitive search,
        // the repository's findOneBy() is an exact database match.
        self::assertNull($this->itemTemplateRepository->findOneByName('rusty dagger'));
    }
}
