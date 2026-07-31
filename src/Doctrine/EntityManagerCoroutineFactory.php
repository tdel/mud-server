<?php

namespace App\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Mints standalone EntityManager instances, one per Swoole coroutine — a
 * PDO handle can't be shared by coroutines that might each be mid-query
 * when the other yields, so every coroutine needs its own DBAL Connection.
 * The ORM Configuration/EventManager are read-only after boot (no
 * listener is ever registered at runtime in this app) and are safe to
 * share across every EntityManager this factory produces.
 */
final class EntityManagerCoroutineFactory
{
    public function __construct(
        #[Autowire(service: 'doctrine.orm.default_entity_manager')]
        private readonly EntityManagerInterface $templateEntityManager,
    ) {
    }

    public function create(): EntityManagerInterface
    {
        $templateConnection = $this->templateEntityManager->getConnection();

        // Reusing the template connection's own DBAL Configuration (not a
        // copy) matters: it's how this app's App\Doctrine\Middleware\Retry*
        // (connection-loss retry, auto-registered by DoctrineBundle as a
        // Driver\Middleware service) ends up wrapping every connection this
        // factory produces too — verified empirically, DriverManager applies
        // whichever middlewares are present on the Configuration it's given.
        $connection = DriverManager::getConnection(
            $templateConnection->getParams(),
            $templateConnection->getConfiguration(),
        );

        return new EntityManager(
            $connection,
            $this->templateEntityManager->getConfiguration(),
            $this->templateEntityManager->getEventManager(),
        );
    }
}
