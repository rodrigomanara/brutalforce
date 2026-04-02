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
