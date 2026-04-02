<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BrutalForce\Initiate;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$options = getopt('', ['scenario::', 'format::']);
$scenarioFilter = $options['scenario'] ?? 'all';
$format = $options['format'] ?? 'text';

if (!is_string($scenarioFilter) || $scenarioFilter === '') {
    $scenarioFilter = 'all';
}

if (!is_string($format) || !in_array($format, ['text', 'json'], true)) {
    fwrite(STDERR, "Invalid --format. Use 'text' or 'json'.\n");
    exit(1);
}

$scenarios = buildScenarios();
if ($scenarioFilter !== 'all') {
    if (!isset($scenarios[$scenarioFilter])) {
        fwrite(STDERR, "Unknown --scenario '{$scenarioFilter}'. Available: all, " . implode(', ', array_keys($scenarios)) . "\n");
        exit(1);
    }

    $scenarios = [$scenarioFilter => $scenarios[$scenarioFilter]];
}

$results = [];
foreach ($scenarios as $name => $runner) {
    $results[] = $runner($name);
}

if ($format === 'json') {
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

printTextResults($results);

function buildScenarios(): array
{
    return [
        'burst' => static function (string $name): array {
            $_SERVER['REMOTE_ADDR'] = '10.10.10.1';
            $_SESSION = [];

            $init = new Initiate();
            $samples = [];
            for ($i = 0; $i < 30; $i++) {
                $samples[] = $init->predict();
                usleep(1000);
            }

            $max = max($samples);
            $rate = $init->Rate();
            $passed = $max <= 0.5 && in_array($rate, [Initiate::RATES[0], Initiate::RATES[1]], true);

            return [
                'scenario' => $name,
                'samples' => count($samples),
                'avg_score' => round(array_sum($samples) / count($samples), 3),
                'final_rate' => $rate,
                'expected' => 'score <= 0.5 and rate in [VERY LOW, LOW]',
                'status' => $passed ? 'PASS' : 'FAIL',
            ];
        },
        'delay-1s' => static fn (string $name): array => runSeededCadenceScenario($name, 1, 0.5, Initiate::RATES[1]),
        'delay-2s' => static fn (string $name): array => runSeededCadenceScenario($name, 2, 1.0, Initiate::RATES[3]),
        'delay-3s' => static fn (string $name): array => runSeededCadenceScenario($name, 3, 1.5, Initiate::RATES[5]),
    ];
}

function runSeededCadenceScenario(string $name, int $secondsBetweenRequests, float $expectedScore, string $expectedRate): array
{
    $_SERVER['REMOTE_ADDR'] = '10.10.10.2';

    $scoreRun = seedCadenceState($secondsBetweenRequests);
    $score = $scoreRun->predict();

    $rateRun = seedCadenceState($secondsBetweenRequests);
    $rate = $rateRun->Rate();

    $passed = abs($score - $expectedScore) <= 0.001 && $rate === $expectedRate;

    return [
        'scenario' => $name,
        'samples' => 2,
        'avg_score' => $score,
        'final_rate' => $rate,
        'expected' => "score={$expectedScore}, rate={$expectedRate}",
        'status' => $passed ? 'PASS' : 'FAIL',
    ];
}

function seedCadenceState(int $secondsBetweenRequests): Initiate
{
    $_SESSION = [];

    $init = new Initiate();
    $ip = $_SERVER['REMOTE_ADDR'];

    $_SESSION['brutalforce.v1'][$ip]['learning'] = [0];
    $_SESSION['brutalforce.v1'][$ip]['initial'] = time() - $secondsBetweenRequests;

    return $init;
}

function printTextResults(array $results): void
{
    echo "BrutalForce simulation results\n";
    echo str_repeat('-', 86) . "\n";
    printf("%-12s %-8s %-10s %-14s %-30s %-6s\n", 'Scenario', 'Samples', 'Score', 'Rate', 'Expected', 'Status');
    echo str_repeat('-', 86) . "\n";

    foreach ($results as $result) {
        printf(
            "%-12s %-8d %-10s %-14s %-30s %-6s\n",
            $result['scenario'],
            $result['samples'],
            (string) $result['avg_score'],
            $result['final_rate'],
            $result['expected'],
            $result['status']
        );
    }

    echo str_repeat('-', 86) . "\n";
}

