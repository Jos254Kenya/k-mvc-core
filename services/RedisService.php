<?php

namespace sigawa\mvccore\services;

use Predis\Client;
use Predis\Response\ServerException;
use Predis\Connection\ConnectionException;

class RedisService
{
    private ?Client $redis = null;

    public function __construct()
    {
        // Lazy loading: Initialize Redis only when needed
    }

    private function getRedis(): ?Client
    {
        if ($this->redis === null) {
            try {
                $this->redis = new Client([
                    'scheme'   => 'tcp',
                    'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port'     => $_ENV['REDIS_PORT'] ?? 6379,
                    'password' => ($_ENV['REDIS_PASSWORD'] !== 'null') ? $_ENV['REDIS_PASSWORD'] : null,
                    'database' => $_ENV['REDIS_DB'] ?? 0,
                ]);
            } catch (ServerException | ConnectionException $e) {
                error_log("Redis connection failed: " . $e->getMessage());
                return null;
            }
        }
        return $this->redis;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $redis = $this->getRedis();
        if (!$redis) return false;

        try {
            return (bool) $redis->setex($key, $ttl, json_encode($value));
        } catch (ConnectionException $e) {
            error_log("Redis set error: " . $e->getMessage());
            return false;
        }
    }

    public function get(string $key): mixed
    {
        $redis = $this->getRedis();
        if (!$redis) return null;

        try {
            $data = $redis->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (ConnectionException $e) {
            error_log("Redis get error: " . $e->getMessage());
            return null;
        }
    }

    public function delete(string $key): bool
    {
        $redis = $this->getRedis();
        if (!$redis) return false;

        try {
            return (bool) $redis->del($key);
        } catch (ConnectionException $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }

    public function exists(string $key): bool
    {
        $redis = $this->getRedis();
        if (!$redis) return false;

        try {
            return (bool) $redis->exists($key);
        } catch (ConnectionException $e) {
            error_log("Redis exists error: " . $e->getMessage());
            return false;
        }
    }

    public function increment(string $key, int $by = 1): int
    {
        $redis = $this->getRedis();
        if (!$redis) return 0;

        try {
            return $redis->incrby($key, $by);
        } catch (ConnectionException $e) {
            error_log("Redis increment error: " . $e->getMessage());
            return 0;
        }
    }

    public function decrement(string $key, int $by = 1): int
    {
        $redis = $this->getRedis();
        if (!$redis) return 0;

        try {
            return $redis->decrby($key, $by);
        } catch (ConnectionException $e) {
            error_log("Redis decrement error: " . $e->getMessage());
            return 0;
        }
    }

    public function rateLimit(string $key, int $maxAttempts, int $window): bool
    {
        $redis = $this->getRedis();
        if (!$redis) return false;

        try {
            $luaScript = "
                local current = redis.call('GET', KEYS[1])
                if current and tonumber(current) >= tonumber(ARGV[1]) then
                    return 0
                else
                    redis.call('INCR', KEYS[1])
                    redis.call('EXPIRE', KEYS[1], ARGV[2])
                    return 1
                end
            ";

            $allowed = $redis->eval($luaScript, 1, $key, $maxAttempts, $window);
            return (bool) $allowed;
        } catch (ConnectionException $e) {
            error_log("Redis rate limit error: " . $e->getMessage());
            return false;
        }
    }

    public function rateLimitWithInfo(string $key, int $maxRequests, int $window)
    {
        $redis = $this->getRedis();
        if (!$redis) return ['allowed' => false, 'remaining' => 0, 'resetTime' => time() + $window];

        try {
            $luaScript = "
                local current = redis.call('GET', KEYS[1])
                if current and tonumber(current) >= tonumber(ARGV[1]) then
                    return {0, tonumber(ARGV[2])}
                else
                    redis.call('INCR', KEYS[1])
                    redis.call('EXPIRE', KEYS[1], ARGV[2])
                    return {1, tonumber(ARGV[1]) - tonumber(current or 0) - 1, ARGV[2]}
                end
            ";

            $result = $redis->eval($luaScript, 1, $key, $maxRequests, $window);
            return [
                'allowed' => (bool) $result[1],
                'remaining' => max(0, (int) $result[2]),
                'resetTime' => time() + $window
            ];
        } catch (ConnectionException $e) {
            error_log("Redis rate limit error: " . $e->getMessage());
            return ['allowed' => false, 'remaining' => 0, 'resetTime' => time() + $window];
        }
    }

    public function acquireLock(string $key, int $ttl = 5): bool
    {
        $redis = $this->getRedis();
        if (!$redis) return false;

        try {
            return (bool) $redis->set($key, 1, 'EX', $ttl, 'NX');
        } catch (ConnectionException $e) {
            error_log("Redis acquire lock error: " . $e->getMessage());
            return false;
        }
    }

    public function releaseLock(string $key): void
    {
        $redis = $this->getRedis();
        if (!$redis) return;

        try {
            $redis->del($key);
        } catch (ConnectionException $e) {
            error_log("Redis release lock error: " . $e->getMessage());
        }
    }
}
