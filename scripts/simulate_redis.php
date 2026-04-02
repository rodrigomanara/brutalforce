<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BrutalForce\Initiate;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$options = getopt('', ['format::']);
$format = $options['format'] ?? 'text';
if (!is_string($format) || !in_array($format, ['text', 'json'], true)) {
    fwrite(STDERR, "Invalid --format. Use 'text' or 'json'.\n");
    exit(1);
}

$redis = new class() {
    public array $rows = [];

    public function hGet(string $key, string $field)
    {
        return $this->rows[$key][$field] ?? null;
    }

    public function hSet(string $key, string $field, string $value): void
    {
        if (!isset($this->rows[$key])) {
            $this->rows[$key] = [];
        }

        $this->rows[$key][$field] = $value;
    }

    public function hDel(string $key, string $field): void
    {
        unset($this->rows[$key][$field]);
    }
};

$guard = Initiate::withRedis(
    $redis,
    ['threshold' => 0.5, 'violation_limit' => 5, 'lock_steps' => [60, 120, 300]],
    ['server' => ['REMOTE_ADDR' => '198.51.100.20'], 'redis_prefix' => 'brutalforce:v1']
);

$results = [];
for ($i = 0; $i < 8; $i++) {
    $decision = $guard->decision();
    $results[] = [
        'step' => $i + 1,
        'blocked' => $decision['blocked'],
        'score' => $decision['score'],
        'rate' => $decision['rate'],
        'retry_after' => $decision['retry_after'],
        'violations' => $decision['violations'],
        'lock_level' => $decision['lock_level'],
    ];

    usleep(1000);
}

$output = [
    'backend' => 'redis-simulated',
    'policy' => $guard->policy(),
    'results' => $results,
    'status' => end($results)['blocked'] ? 'PASS' : 'FAIL',
];

if ($format === 'json') {
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "BrutalForce Redis simulation\n";
echo str_repeat('-', 80) . "\n";
printf("%-6s %-8s %-8s %-14s %-12s %-10s %-8s\n", 'Step', 'Blocked', 'Score', 'Rate', 'RetryAfter', 'Violations', 'Level');
echo str_repeat('-', 80) . "\n";
foreach ($results as $row) {
    printf(
        "%-6d %-8s %-8s %-14s %-12d %-10d %-8d\n",
        $row['step'],
        $row['blocked'] ? 'yes' : 'no',
        (string) $row['score'],
        $row['rate'],
        $row['retry_after'],
        $row['violations'],
        $row['lock_level']
    );
}
echo str_repeat('-', 80) . "\n";
echo 'Status: ' . $output['status'] . "\n";

