<?php

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Swoole\Coroutine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves "the" EntityManager for whichever context is currently
 * running: inside a Swoole coroutine, a dedicated per-coroutine
 * EntityManager (opened once per telnet connection, not per command);
 * outside one (bin/console, PHPUnit), the real container-singleton
 * EntityManager, unchanged — Swoole\Coroutine::getContext() returns null
 * there, so nothing about those code paths behaves any differently than
 * before this class existed.
 *
 * Nothing in a connection's call path may spawn a nested
 * Coroutine::create() — a child coroutine gets its own empty context, not
 * an inherited one, so it would silently fall back to the shared EM
 * instead of the connection's own.
 */
final class CoroutineEntityManagerRegistry
{
    private const string CONTEXT_KEY = 'app_entity_manager';

    public function __construct(
        private readonly EntityManagerCoroutineFactory $factory,
        #[Autowire(service: 'doctrine.orm.default_entity_manager')]
        private readonly EntityManagerInterface $fallbackEntityManager,
    ) {
    }

    public function current(): EntityManagerInterface
    {
        $context = Coroutine::getContext();
        if ($context === null) {
            return $this->fallbackEntityManager;
        }

        return $context[self::CONTEXT_KEY] ??= $this->factory->create();
    }

    /**
     * Call once, from the telnet connection's own coroutine, right after
     * accept — before any query the connection might make.
     */
    public function open(): void
    {
        $context = Coroutine::getContext();
        \assert($context !== null, 'CoroutineEntityManagerRegistry::open() must run inside a coroutine.');

        $context[self::CONTEXT_KEY] = $this->factory->create();
    }

    /**
     * Call once, from the same coroutine, when the connection closes.
     */
    public function close(): void
    {
        $context = Coroutine::getContext();
        if ($context === null || !isset($context[self::CONTEXT_KEY])) {
            return;
        }

        $entityManager = $context[self::CONTEXT_KEY];
        $entityManager->getConnection()->close();
        $entityManager->close();
        unset($context[self::CONTEXT_KEY]);
    }
}
