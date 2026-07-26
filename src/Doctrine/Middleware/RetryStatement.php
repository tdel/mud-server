<?php

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

final class RetryStatement extends AbstractStatementMiddleware
{
    /** @var list<array{int|string, mixed, ParameterType}> */
    private array $boundValues = [];

    private Statement $statement;

    public function __construct(
        private readonly RetryConnection $connection,
        private readonly string $sql,
    ) {
        $this->statement = $connection->driverConnection()->prepare($sql);

        parent::__construct($this->statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->boundValues[] = [$param, $value, $type];
        $this->statement->bindValue($param, $value, $type);
    }

    public function execute(): Result
    {
        try {
            return $this->statement->execute();
        } catch (DriverException $e) {
            if (!RetryConnection::isConnectionLost($e)) {
                throw $e;
            }

            $this->connection->reconnect();
            $this->statement = $this->connection->driverConnection()->prepare($this->sql);

            foreach ($this->boundValues as [$param, $value, $type]) {
                $this->statement->bindValue($param, $value, $type);
            }

            return $this->statement->execute();
        }
    }
}
