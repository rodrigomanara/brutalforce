<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BrutalForce\Initiate;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$guard = Initiate::withRedis(
    $redis,
    [
        'threshold' => 0.5,
        'violation_limit' => 5,
        'lock_steps' => [60, 120, 300],
    ],
    [
        'redis_prefix' => 'brutalforce:v1',
        'trusted_proxies' => ['127.0.0.1'],
    ]
);

$decision = $guard->decision();

if ($decision['blocked']) {
    header('Retry-After: ' . $decision['retry_after']);
    http_response_code(429);
    exit('Too many requests');
}

echo json_encode($decision, JSON_PRETTY_PRINT) . PHP_EOL;

