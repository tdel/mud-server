<?php

namespace App\Repository;

use App\Entity\ItemTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemTemplate>
 */
class ItemTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemTemplate::class);
    }

    public function findOneByName(string $name): ?ItemTemplate
    {
        return $this->findOneBy(['name' => $name]);
    }
}
