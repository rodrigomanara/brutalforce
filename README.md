# BrutalForce

`BrutalForce` is a lightweight PHP library that helps detect suspicious request cadence per client IP using session-backed timing.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require rmanara/brutalforce
```

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use BrutalForce\Initiate;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$guard = new Initiate();

// Text label based on current cadence profile
$rate = $guard->Rate();

// Numeric score (rounded to 3 decimals)
$score = $guard->predict();

if ($score <= 0.5) {
    http_response_code(429);
    exit('Too many requests');
}
```

## Public API

- `Initiate::Rate(): string` returns the current risk label.
- `Initiate::predict(): float` returns the current average timing score.
- `Initiate::decision(): array` returns blocking decision payload.
- `Initiate::isBlocked(): bool` checks if the client is currently locked.
- `Initiate::lockRemaining(): int` returns seconds left in lockout.
- `Initiate::policy(): array` returns effective policy values.
- `Initiate::withRedis(object $redis, array $policy = [], array $options = []): Initiate` creates an instance backed by Redis.

### Blocking Example

```php
$decision = $guard->decision();

if ($decision['blocked']) {
    header('Retry-After: ' . $decision['retry_after']);
    http_response_code(429);
    exit('Too many requests');
}
```

### Policy Configuration

You can configure protection policy per instance:

```php
$guard = new Initiate([
    'threshold' => 0.5,
    'violation_limit' => 5,
    'lock_steps' => [60, 120, 300],
]);
```

Or using environment variables:

```bash
export BRUTALFORCE_THRESHOLD=0.5
export BRUTALFORCE_VIOLATIONS=5
export BRUTALFORCE_LOCK_STEPS=60,120,300
```

Progressive lockouts increase duration for repeated offenders in the same session.

Policy precedence is: constructor overrides > environment variables > built-in defaults.

### Advanced Runtime Options

The second constructor argument accepts runtime integrations:

```php
$guard = new Initiate(
    ['threshold' => 0.5],
    [
        // Session key namespacing/versioning
        'session_namespace' => 'brutalforce',
        'session_version' => 'v1',

        // Trusted proxy strategy
        'trusted_proxies' => ['127.0.0.1'],
        'forwarded_headers' => ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'],

        // Optional distributed backend (phpredis client instance)
        // 'redis' => $redis,
        // 'redis_prefix' => 'brutalforce:v1',

        // Optional deterministic/testing clock (ClockInterface)
        // 'clock' => $clock,
    ]
);
```

### Redis Quick Start

```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$guard = Initiate::withRedis(
    $redis,
    ['threshold' => 0.5, 'violation_limit' => 5, 'lock_steps' => [60, 120, 300]],
    ['redis_prefix' => 'brutalforce:v1']
);
```

See `examples/redis.php` for a complete runnable example.

## Rate Labels

The library maps the score to one of the following labels:

- `VERY LOW`
- `LOW`
- `MEDIUM`
- `MEDIUM HIGH`
- `HIGH`
- `VERY HIGH`

## Running Tests

```bash
vendor/bin/phpunit
```

## Run Simulations

Use the CLI runner to simulate burst and delayed request patterns:

```bash
composer simulate
```

Optional examples:

```bash
php scripts/simulate.php --scenario=delay-2s
php scripts/simulate.php --format=json
```

Redis-backed simulation (local in-memory Redis stub):

```bash
composer simulate:redis
php scripts/simulate_redis.php --format=json
```

## Migration Notes (legacy 5.6-era to modern PHP)

- Runtime target is now PHP 8.2+.
- Core internals use strict return types and safer session access.
- Learning history is bounded per IP to avoid unbounded session growth.
- `Rate()` remains backward compatible.

## Full Documentation

For behavior details, integration guidance, and upgrade notes, see:

- `docs/Documentation.md`

## Project

- Package: `rmanara/brutalforce`
- Repository: https://github.com/rodrigomanara/brutalforce
- License: MIT
