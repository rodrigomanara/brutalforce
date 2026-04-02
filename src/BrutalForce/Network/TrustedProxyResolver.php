<?php

declare(strict_types=1);

namespace BrutalForce\Network;

class TrustedProxyResolver extends RemoteAddrResolver
{
    /** @var string[] */
    private array $trustedProxies;

    /** @var string[] */
    private array $forwardedHeaders;

    /**
     * @param string[] $trustedProxies
     * @param string[] $forwardedHeaders
     */
    public function __construct(array $trustedProxies, array $forwardedHeaders = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'])
    {
        $this->trustedProxies = array_values(array_filter($trustedProxies, 'is_string'));
        $this->forwardedHeaders = array_values(array_filter($forwardedHeaders, 'is_string'));
    }

    public function resolve(array $server): string
    {
        $remoteAddr = parent::resolve($server);
        if (!$this->isTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }

        foreach ($this->forwardedHeaders as $header) {
            $candidate = $this->extractIpFromHeader($server[$header] ?? null);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return $remoteAddr;
    }

    private function isTrustedProxy(string $ip): bool
    {
        if ($this->trustedProxies === []) {
            return false;
        }

        return in_array($ip, $this->trustedProxies, true);
    }

    private function extractIpFromHeader($headerValue): ?string
    {
        if (!is_string($headerValue) || trim($headerValue) === '') {
            return null;
        }

        foreach (explode(',', $headerValue) as $part) {
            $candidate = trim($part);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return null;
    }
}

