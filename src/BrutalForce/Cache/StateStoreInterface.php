<?php

declare(strict_types=1);

namespace BrutalForce\Cache;

interface StateStoreInterface
{
    public function get(string $clientKey, string $key);

    public function set(string $clientKey, string $key, $value): void;

    public function remove(string $clientKey, string $key): void;
}

