<?php

namespace BrutalForce\Cache;

abstract class KeepinMemory
{

    /**
     *
     * @return string|null
     */
    private static function getIP(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected static function setSession(string $key, $value): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (!isset($_SESSION[self::getIP()]) || !is_array($_SESSION[self::getIP()])) {
            $_SESSION[self::getIP()] = [];
        }

        $_SESSION[self::getIP()][$key] = $value;
    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    protected static function getSession(string $key)
    {
        if (isset($_SESSION[self::getIP()][$key])) {
            return $_SESSION[self::getIP()][$key];
        }

        return null;
    }

    protected static function UnsetSession(string $key)
    {
        unset($_SESSION[self::getIP()][$key]);
    }

}
