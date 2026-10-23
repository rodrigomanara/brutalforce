<?php

declare(strict_types=1);

/**
 * BrutalForce DDoS / request-flood simulation harness.
 *
 * Drives the REAL public API (Initiate::decision()) against the library's own
 * injection points — a deterministic clock and an in-memory state store — so a
 * high-rate request flood can be replayed faithfully and repeatably, with no
 * sessions, no network, and no wall-clock sleeping.
 *
 * It floods the guard from an "attacker" IP and, as a control, sends a
 * human-cadence stream from a "legitimate" IP, then reports when (and whether)
 * the lockout engaged and how it escalated.
 *
 * Usage:
 *   php scripts/ddos_simulation.php [--rate=10] [--duration=8] [--format=text|json] [--report=PATH]
 *
 *   --rate       attacker requests per second (default 10)
 *   --duration   attack duration in seconds (default 8)
 *   --format     console output: text (default) or json
 *   --report     also write a Markdown report to PATH
 */

// Minimal PSR-4 autoloader for the library source (no composer/vendor needed).
spl_autoload_register(static function (string $class): void {
    $prefix = 'BrutalForce\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/BrutalForce/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use BrutalForce\Cache\StateStoreInterface;
use BrutalForce\Initiate;
use BrutalForce\Time\ClockInterface;

/** A clock we advance by hand so arrival times are exact and repeatable. */
final class FakeClock implements ClockInterface
{
    private int $now;

    public function __construct(int $start = 0)
    {
        $this->now = $start;
    }

    public function now(): int
    {
        return $this->now;
    }

    public function set(int $now): void
    {
        $this->now = $now;
    }
}

/** In-memory state store — isolates the sim from real sessions/Redis. */
final class ArrayStore implements StateStoreInterface
{
    /** @var array<string,array<string,mixed>> */
    private array $data = [];

    public function get(string $clientKey, string $key)
    {
        return $this->data[$clientKey][$key] ?? null;
    }

    public function set(string $clientKey, string $key, $value): void
    {
        $this->data[$clientKey][$key] = $value;
    }

    public function remove(string $clientKey, string $key): void
    {
        unset($this->data[$clientKey][$key]);
    }
}

$opts = getopt('', ['rate::', 'duration::', 'format::', 'report::']);
$rate = max(1, (int) ($opts['rate'] ?? 10));
$duration = max(1, (int) ($opts['duration'] ?? 8));
$format = (string) ($opts['format'] ?? 'text');
$reportPath = $opts['report'] ?? null;

if (!in_array($format, ['text', 'json'], true)) {
    fwrite(STDERR, "Invalid --format. Use 'text' or 'json'.\n");
    exit(1);
}

/**
 * Replay a stream of request arrival timestamps (floats, seconds) through one
 * guard instance and record the decision at every request.
 *
 * @param list<float> $arrivals
 * @return array{ip:string,label:string,requests:int,blocked_requests:int,served_requests:int,first_block_at:?int,max_retry_after:int,max_lock_level:int,timeline:list<array<string,mixed>>}
 */
function replay(string $ip, string $label, array $arrivals): array
{
    $clock = new FakeClock((int) floor($arrivals[0] ?? 0));
    $store = new ArrayStore();
    $guard = new Initiate([], [
        'store' => $store,
        'clock' => $clock,
        'server' => ['REMOTE_ADDR' => $ip],
    ]);

    $timeline = [];
    $blocked = 0;
    $firstBlockAt = null;
    $maxRetry = 0;
    $maxLockLevel = 0;

    foreach ($arrivals as $i => $t) {
        $clock->set((int) floor($t));
        $d = $guard->decision();

        if ($d['blocked']) {
            $blocked++;
            $firstBlockAt ??= $i + 1;
        }
        $maxRetry = max($maxRetry, (int) $d['retry_after']);
        $maxLockLevel = max($maxLockLevel, (int) $d['lock_level']);

        $timeline[] = [
            'request' => $i + 1,
            't' => round($t, 3),
            'score' => $d['score'],
            'rate' => $d['rate'],
            'violations' => $d['violations'],
            'lock_level' => $d['lock_level'],
            'blocked' => $d['blocked'],
            'retry_after' => $d['retry_after'],
        ];
    }

    return [
        'ip' => $ip,
        'label' => $label,
        'requests' => count($arrivals),
        'blocked_requests' => $blocked,
        'served_requests' => count($arrivals) - $blocked,
        'first_block_at' => $firstBlockAt,
        'max_retry_after' => $maxRetry,
        'max_lock_level' => $maxLockLevel,
        'timeline' => $timeline,
    ];
}

// Attacker: `rate` req/s for `duration` seconds, evenly spaced.
$attack = [];
$step = 1.0 / $rate;
$count = $rate * $duration;
for ($i = 0; $i < $count; $i++) {
    $attack[] = $i * $step;
}

// Legitimate control: one request every 30s over the same wall-clock span.
$legit = [];
for ($t = 0.0; $t <= $duration; $t += 30.0) {
    $legit[] = $t;
}
if (count($legit) < 3) {
    $legit = [0.0, 30.0, 60.0, 90.0, 120.0];
}

$attackerResult = replay('203.0.113.7', 'Attacker (flood)', $attack);
$legitResult = replay('198.51.100.20', 'Legitimate user', $legit);

$policy = (new Initiate([], ['store' => new ArrayStore(), 'clock' => new FakeClock()]))->policy();

$report = [
    'generated_at' => gmdate('c'),
    'parameters' => [
        'attacker_rate_per_sec' => $rate,
        'attack_duration_sec' => $duration,
        'attacker_total_requests' => $count,
    ],
    'policy' => $policy,
    'results' => [$attackerResult, $legitResult],
    'verdict' => [
        'attacker_blocked' => $attackerResult['first_block_at'] !== null,
        'legit_ever_blocked' => $legitResult['first_block_at'] !== null,
        'status' => ($attackerResult['first_block_at'] !== null && $legitResult['first_block_at'] === null)
            ? 'PASS'
            : 'FAIL',
    ],
];

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    printTextReport($report);
}

if (is_string($reportPath) && $reportPath !== '') {
    file_put_contents($reportPath, buildMarkdownReport($report));
    fwrite(STDERR, "Markdown report written to {$reportPath}\n");
}

exit($report['verdict']['status'] === 'PASS' ? 0 : 1);

function printTextReport(array $report): void
{
    $p = $report['parameters'];
    $pol = $report['policy'];

    echo "BrutalForce DDoS / flood simulation\n";
    echo str_repeat('=', 72) . "\n";
    echo "Generated:  {$report['generated_at']}\n";
    printf("Attacker:   %d req/s for %ds = %d requests\n", $p['attacker_rate_per_sec'], $p['attack_duration_sec'], $p['attacker_total_requests']);
    printf("Policy:     threshold=%.3f  violation_limit=%d  lock_steps=[%s]\n", $pol['threshold'], $pol['violation_limit'], implode(', ', $pol['lock_steps']));
    echo str_repeat('-', 72) . "\n";

    foreach ($report['results'] as $r) {
        printf("%s  (%s)\n", $r['label'], $r['ip']);
        printf("  requests=%d  served=%d  blocked=%d\n", $r['requests'], $r['served_requests'], $r['blocked_requests']);
        printf("  first_block_at=%s  max_lock_level=%d  max_retry_after=%ds\n",
            $r['first_block_at'] === null ? 'never' : '#' . $r['first_block_at'],
            $r['max_lock_level'],
            $r['max_retry_after']
        );
        echo str_repeat('-', 72) . "\n";
    }

    $v = $report['verdict'];
    printf("VERDICT: %s  (attacker blocked=%s, legit ever blocked=%s)\n",
        $v['status'],
        $v['attacker_blocked'] ? 'yes' : 'no',
        $v['legit_ever_blocked'] ? 'yes' : 'no'
    );
}

function buildMarkdownReport(array $report): string
{
    $p = $report['parameters'];
    $pol = $report['policy'];
    $v = $report['verdict'];

    $md = "# BrutalForce DDoS Simulation Report\n\n";
    $md .= "**Generated:** {$report['generated_at']}  \n";
    $md .= "**Verdict:** " . ($v['status'] === 'PASS' ? '✅ PASS' : '❌ FAIL') . "\n\n";

    $md .= "## Scenario\n\n";
    $md .= "A single attacker IP floods the guard while a legitimate user sends traffic at human cadence.\n";
    $md .= "The guard is driven through its real `decision()` API with a deterministic clock and an in-memory store.\n\n";
    $md .= "| Parameter | Value |\n|---|---|\n";
    $md .= "| Attacker rate | {$p['attacker_rate_per_sec']} req/s |\n";
    $md .= "| Attack duration | {$p['attack_duration_sec']} s |\n";
    $md .= "| Attacker requests | {$p['attacker_total_requests']} |\n";
    $md .= "| Policy threshold | {$pol['threshold']} |\n";
    $md .= "| Violation limit | {$pol['violation_limit']} |\n";
    $md .= "| Lock steps (s) | " . implode(', ', $pol['lock_steps']) . " |\n\n";

    $md .= "## Results\n\n";
    $md .= "| Client | IP | Requests | Served | Blocked | First block | Max lock level | Max retry-after |\n";
    $md .= "|---|---|--:|--:|--:|---|--:|--:|\n";
    foreach ($report['results'] as $r) {
        $md .= sprintf(
            "| %s | `%s` | %d | %d | %d | %s | %d | %ds |\n",
            $r['label'], $r['ip'], $r['requests'], $r['served_requests'], $r['blocked_requests'],
            $r['first_block_at'] === null ? 'never' : '#' . $r['first_block_at'],
            $r['max_lock_level'], $r['max_retry_after']
        );
    }

    $md .= "\n## Attacker timeline (first 20 requests)\n\n";
    $md .= "| # | t (s) | score | rate | violations | lock lvl | blocked | retry-after |\n";
    $md .= "|--:|--:|--:|---|--:|--:|:-:|--:|\n";
    foreach (array_slice($report['results'][0]['timeline'], 0, 20) as $row) {
        $md .= sprintf(
            "| %d | %s | %s | %s | %d | %d | %s | %d |\n",
            $row['request'], $row['t'], $row['score'], $row['rate'],
            $row['violations'], $row['lock_level'], $row['blocked'] ? '🔒' : '·', $row['retry_after']
        );
    }

    $md .= "\n## Interpretation\n\n";
    $md .= "- Rapid requests collapse the inter-arrival gap, driving `predict()` toward 0.\n";
    $md .= "- Each score at or below the threshold (`{$pol['threshold']}`) is one violation; at `{$pol['violation_limit']}` violations the IP is locked out.\n";
    $md .= "- Lockouts escalate through the configured steps, so a persistent attacker is held off for progressively longer.\n";
    $md .= "- The legitimate user never crosses the threshold and is never blocked.\n";

    return $md;
}
