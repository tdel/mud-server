<?php

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class RetryDriver extends AbstractDriverMiddleware
{
    public function __construct(
        private readonly Driver $driver,
    ) {
        parent::__construct($driver);
    }

    public function connect(#[\SensitiveParameter] array $params): DriverConnection
    {
        return new RetryConnection($this->driver, $params);
    }
}
