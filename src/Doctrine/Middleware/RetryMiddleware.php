<?php

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Registered automatically by DoctrineBundle (any Driver\Middleware
 * implementation is auto-tagged "doctrine.middleware") — wraps every
 * connection with retry-on-connection-lost behaviour, transparently to
 * every caller (repositories, DQL, raw SQL...).
 */
final class RetryMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new RetryDriver($driver);
    }
}
