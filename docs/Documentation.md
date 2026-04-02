# BrutalForce Documentation

## Overview

`BrutalForce` helps identify suspicious request cadence by tracking request timing per client IP in session storage.

The library exposes a simple API and can be integrated in plain PHP or framework middleware layers.

## How It Works

1. A request is associated with a client IP (`$_SERVER['REMOTE_ADDR']`).
2. The previous request timestamp for that IP is read from session.
3. The time delta (in seconds) is appended to a bounded learning window.
4. `predict()` returns the average of that window.
5. `Rate()` maps the numeric score into a human-readable label.

## Session Requirements

Before using the library, make sure a session is active:

```php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
```

If no IP is available, the library falls back to `0.0.0.0` internally.

## API Reference

### `Initiate::Rate(): string`

Returns a rate label based on the current prediction:

- `VERY LOW`
- `LOW`
- `MEDIUM`
- `MEDIUM HIGH`
- `HIGH`
- `VERY HIGH`

### `Initiate::predict(): float`

Returns the current average request-timing score (rounded to 3 decimals).

- Lower scores generally mean faster repeated requests.
- Higher scores generally mean slower request cadence.

### `Initiate::decision(): array`

Returns an enforcement payload:

- `blocked` (`bool`): if request should be denied.
- `score` (`float`): current cadence score.
- `rate` (`string`): current label.
- `retry_after` (`int`): seconds until unlock when blocked.
- `violations` (`int`): current violation counter.

### `Initiate::isBlocked(): bool`

Returns whether the current IP is in temporary lockout.

### `Initiate::lockRemaining(): int`

Returns remaining lockout seconds for the current IP.

### `Initiate::policy(): array`

Returns effective policy values currently applied by the instance.

### `Initiate::withRedis(object $redis, array $policy = [], array $options = []): Initiate`

Creates an instance that stores enforcement state in Redis (distributed lockouts across app servers).

### `new Initiate(array $policy = [])`

Optional runtime policy overrides:

- `threshold` (`float`): suspicious cadence threshold.
- `violation_limit` (`int`): violations before lockout.
- `lock_steps` (`int[]`): progressive lock durations in seconds.

### `new Initiate(array $policy = [], array $options = [])`

Optional advanced runtime options:

- `session_namespace` (`string`): session root namespace (default `brutalforce`).
- `session_version` (`string`): session schema version (default `v1`).
- `trusted_proxies` (`string[]`): proxies allowed to supply forwarded client IP.
- `forwarded_headers` (`string[]`): header priority for forwarded IP lookup.
- `clock` (`ClockInterface`): inject deterministic clock for testing.
- `redis` (`object`): phpredis-compatible client for distributed state.
- `redis_prefix` (`string`): key prefix for Redis store.
- `store` (`StateStoreInterface`): custom state backend implementation.

Redis convenience helper:

```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$guard = Initiate::withRedis(
    $redis,
    ['threshold' => 0.5, 'violation_limit' => 5, 'lock_steps' => [60, 120, 300]],
    ['redis_prefix' => 'brutalforce:v1']
);
```

Full runnable example: `examples/redis.php`.

## Score-to-Label Mapping

Current mapping rules:

- `0.0` => `VERY LOW`
- `> 0.0` and `<= 0.5` => `LOW`
- `> 0.5` and `<= 0.8` => `MEDIUM`
- `> 0.8` and `<= 1.0` => `MEDIUM HIGH`
- `> 1.0` and `<= 1.2` => `HIGH`
- `> 1.2` => `VERY HIGH`

## Integration Example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use BrutalForce\Initiate;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$guard = new Initiate();
$score = $guard->predict();
$rate = $guard->Rate();

if ($score <= 0.5) {
    http_response_code(429);
    exit('Too many requests');
}
```

## Upgrade Notes (from legacy 5.6-era usage)

- Project target is PHP 8.2+.
- Core classes now use typed method signatures.
- Session handling is more defensive for missing keys and missing IP.
- Learning data is bounded to reduce long-session memory growth.
- Public `Rate()` behavior remains compatible with existing usage.
- A temporary lockout policy is now available through `decision()`.

## Protection Policy

Current built-in defaults:

- Suspicious cadence threshold: `score <= 0.5`
- Violations to trigger lock: `5`
- Progressive lock durations: `60, 120, 300` seconds

Policy can be configured in two ways.

Constructor override:

```php
$guard = new Initiate([
    'threshold' => 0.5,
    'violation_limit' => 5,
    'lock_steps' => [60, 120, 300],
]);
```

Environment variables:

```bash
export BRUTALFORCE_THRESHOLD=0.5
export BRUTALFORCE_VIOLATIONS=5
export BRUTALFORCE_LOCK_STEPS=60,120,300
```

Alternative single lock duration env var:

```bash
export BRUTALFORCE_LOCK_SECONDS=60
```

Policy precedence is: constructor overrides > environment variables > built-in defaults.

If `trusted_proxies` is not configured, `REMOTE_ADDR` is always used.

Example enforcement in your application:

```php
$decision = $guard->decision();

if ($decision['blocked']) {
    header('Retry-After: ' . $decision['retry_after']);
    http_response_code(429);
    exit('Too many requests');
}
```

## Testing

Run the test suite with:

```bash
vendor/bin/phpunit
```

## Simulation Runner

Run built-in traffic simulations from CLI:

```bash
composer simulate
```

Run a single scenario:

```bash
php scripts/simulate.php --scenario=delay-3s
```

Output as JSON:

```bash
php scripts/simulate.php --format=json
```

Redis-backed simulation (local in-memory Redis stub):

```bash
composer simulate:redis
php scripts/simulate_redis.php --format=json
```

