<?php

declare(strict_types=1);

namespace BrutalForce\Cache;

class SessionStore implements StateStoreInterface
{
    private string $namespace;

    public function __construct(string $namespace = 'brutalforce', string $version = 'v1')
    {
        $namespace = trim($namespace) !== '' ? trim($namespace) : 'brutalforce';
        $version = trim($version) !== '' ? trim($version) : 'v1';
        $this->namespace = $namespace . '.' . $version;
    }

    public function get(string $clientKey, string $key)
    {
        $this->ensureSessionArray();

        return $_SESSION[$this->namespace][$clientKey][$key] ?? null;
    }

    public function set(string $clientKey, string $key, $value): void
    {
        $this->ensureSessionArray();

        if (!isset($_SESSION[$this->namespace][$clientKey]) || !is_array($_SESSION[$this->namespace][$clientKey])) {
            $_SESSION[$this->namespace][$clientKey] = [];
        }

        $_SESSION[$this->namespace][$clientKey][$key] = $value;
    }

    public function remove(string $clientKey, string $key): void
    {
        $this->ensureSessionArray();
        unset($_SESSION[$this->namespace][$clientKey][$key]);
    }

    private function ensureSessionArray(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (!isset($_SESSION[$this->namespace]) || !is_array($_SESSION[$this->namespace])) {
            $_SESSION[$this->namespace] = [];
        }
    }
}

