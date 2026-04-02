<?php

declare(strict_types=1);

namespace BrutalForce\Cache;

class RedisStore implements StateStoreInterface
{
    private object $redis;
    private string $prefix;

    public function __construct(object $redis, string $prefix = 'brutalforce:v1')
    {
        $this->redis = $redis;
        $this->prefix = trim($prefix) !== '' ? trim($prefix) : 'brutalforce:v1';
    }

    public function get(string $clientKey, string $key)
    {
        $payload = $this->redis->hGet($this->toRedisKey($clientKey), $key);
        if (!is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    public function set(string $clientKey, string $key, $value): void
    {
        $this->redis->hSet($this->toRedisKey($clientKey), $key, json_encode($value));
    }

    public function remove(string $clientKey, string $key): void
    {
        $this->redis->hDel($this->toRedisKey($clientKey), $key);
    }

    private function toRedisKey(string $clientKey): string
    {
        return $this->prefix . ':' . $clientKey;
    }
}

