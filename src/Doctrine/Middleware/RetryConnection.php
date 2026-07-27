<?php

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class RetryConnection extends AbstractConnectionMiddleware
{
    private DriverConnection $connection;

    public function __construct(
        private readonly Driver $driver,
        #[\SensitiveParameter]
        private readonly array $params,
    ) {
        $this->connection = $driver->connect($params);

        parent::__construct($this->connection);
    }

    public function prepare(string $sql): Statement
    {
        return new RetryStatement($this, $sql);
    }

    public function query(string $sql): Result
    {
        return $this->retry(fn (): Result => $this->connection->query($sql));
    }

    public function exec(string $sql): int|string
    {
        return $this->retry(fn (): int|string => $this->connection->exec($sql));
    }

    public function driverConnection(): DriverConnection
    {
        return $this->connection;
    }

    public function reconnect(): DriverConnection
    {
        return $this->connection = $this->driver->connect($this->params);
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function retry(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (DriverException $e) {
            if (!self::isConnectionLost($e)) {
                throw $e;
            }

            $this->reconnect();

            return $fn();
        }
    }

    private const array LOST_CONNECTION_SQLSTATES = ['08000', '08001', '08003', '08004', '08006', '57P01', '57P02', '57P03'];

    /**
     * PostgreSQL SQLSTATE codes for connection loss/failure (class 08 "Connection
     * Exception") and admin-initiated disconnects (57P01-57P03: terminated,
     * crashed, cannot connect now).
     */
    public static function isConnectionLost(DriverException $e): bool
    {
        return in_array($e->getSQLState(), self::LOST_CONNECTION_SQLSTATES, true);
    }
}
