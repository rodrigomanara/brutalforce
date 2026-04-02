<?php

namespace BrutalForce;

use BrutalForce\Cache\RedisStore;
use BrutalForce\Cache\SessionStore;
use BrutalForce\Cache\StateStoreInterface;
use BrutalForce\Firewall\Pass;
use BrutalForce\Network\ClientIpResolverInterface;
use BrutalForce\Network\RemoteAddrResolver;
use BrutalForce\Network\TrustedProxyResolver;
use BrutalForce\Time\ClockInterface;

class Initiate extends Pass
{

    public const RATES = [
        0 => 'VERY LOW',
        1 => 'LOW',
        2 => 'MEDIUM',
        3 => 'MEDIUM HIGH',
        4 => 'HIGH',
        5 => 'VERY HIGH',
    ];

    /**
     * @param array<string,mixed> $policy
     * @param array<string,mixed> $options
     */
    public function __construct(array $policy = [], array $options = [])
    {
        $store = $this->resolveStore($options);
        $resolver = $this->resolveIpResolver($options);
        $server = isset($options['server']) && is_array($options['server']) ? $options['server'] : $_SERVER;
        $clientKey = $resolver->resolve($server);
        $clock = isset($options['clock']) && $options['clock'] instanceof ClockInterface ? $options['clock'] : null;

        parent::__construct($store, $clientKey, $clock, $policy);
    }

    /**
     * @param array<string,mixed> $policy
     * @param array<string,mixed> $options
     */
    public static function withRedis(object $redis, array $policy = [], array $options = []): self
    {
        $options['redis'] = $redis;

        return new self($policy, $options);
    }

    /**
     *
     * @return string
     */
    public function Rate(): string
    {
        $p = $this->predict();

        return $this->rateForScore($p);
    }

    public function isBlocked(): bool
    {
        return $this->isLockActive();
    }

    public function lockRemaining(): int
    {
        return $this->getLockRemaining();
    }

    /**
     *
     * @return array{threshold:float,violation_limit:int,lock_steps:array<int,int>}
     */
    public function policy(): array
    {
        return $this->getPolicy();
    }

    /**
     *
     * @return array{blocked:bool,score:float,rate:string,retry_after:int,violations:int,lock_level:int,policy:array{threshold:float,violation_limit:int,lock_steps:array<int,int>}}
     */
    public function decision(): array
    {
        $score = $this->predict();
        $this->evaluateProtection($score);

        $blocked = $this->isLockActive();

        return [
            'blocked' => $blocked,
            'score' => $score,
            'rate' => $this->rateForScore($score),
            'retry_after' => $blocked ? $this->getLockRemaining() : 0,
            'violations' => $this->getViolations(),
            'lock_level' => $this->getLockLevel(),
            'policy' => $this->policy(),
        ];
    }

    private function resolveStore(array $options): StateStoreInterface
    {
        if (isset($options['store']) && $options['store'] instanceof StateStoreInterface) {
            return $options['store'];
        }

        if (isset($options['redis']) && is_object($options['redis'])) {
            $prefix = isset($options['redis_prefix']) && is_string($options['redis_prefix'])
                ? $options['redis_prefix']
                : 'brutalforce:v1';

            return new RedisStore($options['redis'], $prefix);
        }

        $namespace = isset($options['session_namespace']) && is_string($options['session_namespace'])
            ? $options['session_namespace']
            : 'brutalforce';
        $version = isset($options['session_version']) && is_string($options['session_version'])
            ? $options['session_version']
            : 'v1';

        return new SessionStore($namespace, $version);
    }

    private function resolveIpResolver(array $options): ClientIpResolverInterface
    {
        if (isset($options['ip_resolver']) && $options['ip_resolver'] instanceof ClientIpResolverInterface) {
            return $options['ip_resolver'];
        }

        $trustedProxies = isset($options['trusted_proxies']) && is_array($options['trusted_proxies'])
            ? $options['trusted_proxies']
            : [];

        if ($trustedProxies !== []) {
            $headers = isset($options['forwarded_headers']) && is_array($options['forwarded_headers'])
                ? $options['forwarded_headers']
                : ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];

            return new TrustedProxyResolver($trustedProxies, $headers);
        }

        return new RemoteAddrResolver();
    }

    private function rateForScore(float $p): string
    {
        return match (true) {
            $p === 0.0 => static::RATES[0],
            $p > 0.0 && $p <= 0.5 => static::RATES[1],
            $p > 0.5 && $p <= 0.8 => static::RATES[2],
            $p > 0.8 && $p <= 1.0 => static::RATES[3],
            $p > 1.0 && $p <= 1.2 => static::RATES[4],
            default => static::RATES[5],
        };
    }

}
