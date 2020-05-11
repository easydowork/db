<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\DB;

use PDO;
use Swoole\Database\PDOStatementProxy;

class DB
{
    protected $pool;

    /** @var PDO */
    protected $pdo;

    protected $statement_cache = [];

    protected $statement_num = 100;

    public function __construct($config = null)
    {
        if (! empty($config)) {
            $this->pool = \Simps\DB\PDO::getInstance($config);
        } else {
            $this->pool = \Simps\DB\PDO::getInstance();
        }
        $this->pdo = $this->pool->getConnection();
    }

    public function __destruct()
    {
        $this->pool->close($this->pdo);
    }

    public function __call($name, $arguments)
    {
        return $this->pdo->{$name}(...$arguments);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->is_transaction = true;
    }

    public function commit(): void
    {
        $this->pdo->commit();
        $this->pdo->is_transaction = false;
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
        $this->pdo->is_transaction = false;
    }

    public function query(string $query, array $bindings = []): array
    {
        $statement = $this->cacheStatement($query);

        $this->bindValues($statement, $bindings);

        $statement->execute();

        return $statement->fetchAll();
    }

    public function fetch(string $query, array $bindings = [])
    {
        $records = $this->query($query, $bindings);

        return array_shift($records);
    }

    public function execute(string $query, array $bindings = []): int
    {
        $statement = $this->cacheStatement($query);

        $this->bindValues($statement, $bindings);

        $statement->execute();

        return $statement->rowCount();
    }

    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }

    public function insert(string $query, array $bindings = []): int
    {
        $statement = $this->cacheStatement($query);

        $this->bindValues($statement, $bindings);

        $statement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function close($connection = null)
    {
        $this->pool->close($connection);
    }

    protected function bindValues(PDOStatementProxy $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    protected function cacheStatement(string $query)
    {
        if (isset($this->statement_cache[$query])) {
            $statement = $this->statement_cache[$query];
        } else {
            $statement = $this->pdo->prepare($query);
            if (! empty($statement)) {
                $this->statement_cache[$query] = $statement;
                if (count($this->statement_cache) > $this->statement_num) {
                    array_shift($this->statement_cache);
                }
            }
        }
        return $statement;
    }
}
