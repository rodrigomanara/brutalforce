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

## Testing

Run the test suite with:

```bash
vendor/bin/phpunit
```

