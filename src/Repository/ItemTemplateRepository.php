<?php

namespace App\Repository;

use App\Entity\ItemTemplate;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Plain EntityManagerInterface-backed repository — deliberately not a
 * ServiceEntityRepository. Doctrine bundle's ContainerRepositoryFactory
 * resolves a ServiceEntityRepository straight from the container,
 * ignoring whichever EntityManager it was asked for, which would silently
 * defeat the per-coroutine EntityManager scoping the telnet server relies
 * on (see App\Doctrine\EntityManagerProxy).
 */
final class ItemTemplateRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findOneByName(string $name): ?ItemTemplate
    {
        return $this->entityManager->getRepository(ItemTemplate::class)->findOneBy(['name' => $name]);
    }
}
