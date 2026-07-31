<?php

namespace App\Doctrine;

use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;

/**
 * Implements EntityManagerInterface by delegating every call to whichever
 * real EntityManager CoroutineEntityManagerRegistry::current() resolves —
 * a different one per Swoole coroutine, or the real shared singleton
 * outside a coroutine. This is the service the DI container hands out
 * for `EntityManagerInterface` (see config/services.yaml) so that none of
 * the app's ~15 existing injection sites need to change.
 *
 * Delegation is explicit per method, not via __call(), so PHPStan
 * (phpstan-doctrine, level 5) keeps real return types everywhere an
 * EntityManagerInterface is used.
 */
final class EntityManagerProxy implements EntityManagerInterface
{
    public function __construct(
        private readonly CoroutineEntityManagerRegistry $registry,
    ) {
    }

    public function getRepository(string $className): EntityRepository
    {
        return $this->registry->current()->getRepository($className);
    }

    public function getCache(): ?Cache
    {
        return $this->registry->current()->getCache();
    }

    public function getConnection(): Connection
    {
        return $this->registry->current()->getConnection();
    }

    // PHPStan flags this method's return type against ObjectManager's
    // generic contract — Doctrine\ORM\Mapping\ClassMetadataFactory does
    // implement Persistence\Mapping\ClassMetadataFactory<ClassMetadata<object>>
    // (verified: is_subclass_of() is true), but phpstan-doctrine only
    // resolves that covariance for the concrete Doctrine\ORM\EntityManager
    // class by name, not for any other implementer of the interface.
    // Suppressed in phpstan-baseline.neon rather than fought further.
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->registry->current()->getMetadataFactory();
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->registry->current()->getExpressionBuilder();
    }

    public function beginTransaction(): void
    {
        $this->registry->current()->beginTransaction();
    }

    public function wrapInTransaction(callable $func): mixed
    {
        return $this->registry->current()->wrapInTransaction($func);
    }

    public function commit(): void
    {
        $this->registry->current()->commit();
    }

    public function rollback(): void
    {
        $this->registry->current()->rollback();
    }

    public function createQuery(string $dql = ''): Query
    {
        return $this->registry->current()->createQuery($dql);
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->registry->current()->createNativeQuery($sql, $rsm);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->registry->current()->createQueryBuilder();
    }

    public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->registry->current()->find($className, $id, $lockMode, $lockVersion);
    }

    public function refresh(object $object, LockMode|int|null $lockMode = null): void
    {
        $this->registry->current()->refresh($object, $lockMode);
    }

    public function getReference(string $entityName, mixed $id): ?object
    {
        return $this->registry->current()->getReference($entityName, $id);
    }

    public function close(): void
    {
        $this->registry->current()->close();
    }

    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        $this->registry->current()->lock($entity, $lockMode, $lockVersion);
    }

    public function getEventManager(): EventManager
    {
        return $this->registry->current()->getEventManager();
    }

    public function getConfiguration(): Configuration
    {
        return $this->registry->current()->getConfiguration();
    }

    public function isOpen(): bool
    {
        return $this->registry->current()->isOpen();
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->registry->current()->getUnitOfWork();
    }

    public function newHydrator(string|int $hydrationMode): AbstractHydrator
    {
        return $this->registry->current()->newHydrator($hydrationMode);
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->registry->current()->getProxyFactory();
    }

    public function getFilters(): FilterCollection
    {
        return $this->registry->current()->getFilters();
    }

    public function isFiltersStateClean(): bool
    {
        return $this->registry->current()->isFiltersStateClean();
    }

    public function hasFilters(): bool
    {
        return $this->registry->current()->hasFilters();
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->registry->current()->getClassMetadata($className);
    }

    public function persist(object $object): void
    {
        $this->registry->current()->persist($object);
    }

    public function remove(object $object): void
    {
        $this->registry->current()->remove($object);
    }

    public function clear(): void
    {
        $this->registry->current()->clear();
    }

    public function detach(object $object): void
    {
        $this->registry->current()->detach($object);
    }

    public function flush(): void
    {
        $this->registry->current()->flush();
    }

    public function initializeObject(object $obj): void
    {
        $this->registry->current()->initializeObject($obj);
    }

    public function isUninitializedObject(mixed $value): bool
    {
        return $this->registry->current()->isUninitializedObject($value);
    }

    public function contains(object $object): bool
    {
        return $this->registry->current()->contains($object);
    }
}
