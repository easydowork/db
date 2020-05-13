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

class BaseRedis
{
    protected $pool;

    protected $connection;

    public function __construct($config = null)
    {
        if (! empty($config)) {
            $this->pool = Redis::getInstance($config);
        } else {
            $this->pool = Redis::getInstance();
        }
        $this->connection = $this->pool->getConnection();
    }

    public function __destruct()
    {
        $this->pool->close($this->connection);
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function close($connection = null)
    {
        $this->pool->close($connection);
    }
}
