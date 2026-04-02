<?php

namespace BrutalForce\Cache;

abstract class KeepinMemory
{
    protected StateStoreInterface $store;
    protected string $clientKey;

    public function __construct(StateStoreInterface $store, string $clientKey)
    {
        $this->store = $store;
        $this->clientKey = $clientKey !== '' ? $clientKey : '0.0.0.0';
    }

    protected function setSession(string $key, $value): void
    {
        $this->store->set($this->clientKey, $key, $value);
    }

    protected function getSession(string $key)
    {
        return $this->store->get($this->clientKey, $key);
    }

    protected function unsetSession(string $key): void
    {
        $this->store->remove($this->clientKey, $key);
    }

}
