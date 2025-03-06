<?php

namespace sigawa\mvccore\services;

use Predis\Client;

class RedisService
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port'   => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] !== 'null' ? $_ENV['REDIS_PASSWORD'] : null,
            'database' => $_ENV['REDIS_DB'] ?? 0,
        ]);
    }
    public function getClient()
    {
        return $this->redis;
    }
    public function set($key, $value, $ttl = 3600) // Default cache for 1 hour
    {
        $this->redis->setex($key, $ttl, json_encode($value));
    }

    public function get($key)
    {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }

    public function delete($key)
    {
        $this->redis->del($key);
    }

    public function flush()
    {
        $this->redis->flushdb(); // Clears all cached data
    }
    
}
