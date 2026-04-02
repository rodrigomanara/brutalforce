<?php

declare(strict_types=1);

namespace BrutalForce\Network;

class RemoteAddrResolver implements ClientIpResolverInterface
{
    public function resolve(array $server): string
    {
        $ip = $server['REMOTE_ADDR'] ?? '0.0.0.0';

        return $this->normalizeIp($ip);
    }

    protected function normalizeIp($ip): string
    {
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

