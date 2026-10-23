<?php

namespace BrutalForce\Test\BrutalForce;

use BrutalForce\Cache\StateStoreInterface;
use BrutalForce\Initiate;
use BrutalForce\Time\ClockInterface;
use PHPUnit\Framework\TestCase;

class SimulationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '192.168.0.2';
        $_SESSION = [];
    }

    public function testBurstSimulationRemainsInLowBand(): void
    {
        $init = new Initiate();
        $predictions = [];

        for ($i = 0; $i < 30; $i++) {
            $predictions[] = $init->predict();
            usleep(1000);
        }

        $this->assertLessThanOrEqual(0.5, max($predictions));
        $this->assertContains($init->Rate(), [Initiate::RATES[0], Initiate::RATES[1]]);
    }

    public function testSimulatedOneSecondCadenceMapsToLow(): void
    {
        $scoreRun = $this->seedCadenceState(1);
        $this->assertEqualsWithDelta(0.5, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(1);
        $this->assertSame(Initiate::RATES[1], $rateRun->Rate());
    }

    public function testSimulatedTwoSecondCadenceMapsToMediumHigh(): void
    {
        $scoreRun = $this->seedCadenceState(2);
        $this->assertEqualsWithDelta(1.0, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(2);
        $this->assertSame(Initiate::RATES[3], $rateRun->Rate());
    }

    public function testSimulatedThreeSecondCadenceMapsToVeryHigh(): void
    {
        $scoreRun = $this->seedCadenceState(3);
        $this->assertEqualsWithDelta(1.5, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(3);
        $this->assertSame(Initiate::RATES[5], $rateRun->Rate());
    }

    private function seedCadenceState(int $secondsBetweenRequests): Initiate
    {
        $init = new Initiate();
        $ip = $_SERVER['REMOTE_ADDR'];

        $_SESSION['brutalforce.v1'][$ip]['learning'] = [0];
        $_SESSION['brutalforce.v1'][$ip]['initial'] = time() - $secondsBetweenRequests;

        return $init;
    }

    /**
     * A request flood (requests arriving within the same second, so inter-arrival
     * gaps are 0) must lock the attacker out exactly once the violation limit is
     * reached, and the lockout must carry a positive retry-after.
     */
    public function testAttackerFloodIsLockedOutAfterViolationLimit(): void
    {
        $clock = $this->advancingClock();
        $guard = $this->floodGuard('203.0.113.7', $clock);
        $limit = $guard->policy()['violation_limit'];

        $firstBlockAt = null;
        for ($i = 1; $i <= $limit + 3; $i++) {
            $decision = $guard->decision();
            if ($decision['blocked'] && $firstBlockAt === null) {
                $firstBlockAt = $i;
            }
        }

        $this->assertSame($limit, $firstBlockAt, 'Lockout should engage on the request that hits the violation limit.');
        $this->assertTrue($guard->isBlocked());
        $this->assertGreaterThan(0, $guard->lockRemaining());
        $this->assertSame(1, $guard->decision()['lock_level']);
    }

    /**
     * Over a sustained flood the overwhelming majority of requests are blocked,
     * while only the pre-lock warm-up requests are served.
     */
    public function testSustainedFloodBlocksMajorityOfRequests(): void
    {
        $clock = $this->advancingClock();
        $guard = $this->floodGuard('203.0.113.8', $clock);
        $limit = $guard->policy()['violation_limit'];

        $served = 0;
        $blocked = 0;
        for ($i = 0; $i < 80; $i++) {
            if ($guard->decision()['blocked']) {
                $blocked++;
            } else {
                $served++;
            }
            $clock->advance(0); // requests keep arriving inside the lock window
        }

        $this->assertSame($limit - 1, $served, 'Only warm-up requests before the lock should be served.');
        $this->assertSame(80 - ($limit - 1), $blocked);
        $this->assertGreaterThan($served, $blocked);
    }

    /**
     * Human-cadence traffic (large inter-arrival gaps) must never trip the guard.
     */
    public function testLegitimateCadenceIsNeverBlocked(): void
    {
        $clock = $this->advancingClock();
        $guard = $this->floodGuard('198.51.100.20', $clock);

        $maxScore = 0.0;
        for ($i = 0; $i < 20; $i++) {
            $decision = $guard->decision();
            $this->assertFalse($decision['blocked'], "Legitimate request #{$i} should not be blocked.");
            $maxScore = max($maxScore, $decision['score']);
            $clock->advance(30); // one request every 30 seconds
        }

        $this->assertGreaterThan(0.5, $maxScore, 'Slow cadence should score well above the block threshold.');
        $this->assertSame(0, $guard->lockRemaining());
    }

    /**
     * A repeat offender who resumes flooding after each lock expires is held off
     * for progressively longer, stepping through the configured lock_steps.
     */
    public function testRepeatOffenderLockoutsEscalate(): void
    {
        $ip = '203.0.113.9';
        $clock = $this->advancingClock();
        $store = $this->memoryStore();
        $guard = new Initiate(
            ['threshold' => 0.5, 'violation_limit' => 1, 'lock_steps' => [2, 4, 6]],
            ['store' => $store, 'clock' => $clock, 'server' => ['REMOTE_ADDR' => $ip]]
        );

        $retryAfters = [];
        $levels = [];
        foreach ([2, 4, 6] as $i => $expectedStep) {
            $decision = $guard->decision();
            $this->assertTrue($decision['blocked'], "Offense #{$i} should lock the attacker out.");
            $this->assertLessThanOrEqual($expectedStep, $decision['retry_after']);
            $retryAfters[] = $decision['retry_after'];
            $levels[] = $decision['lock_level'];

            // Re-offend immediately: expire the lock without advancing the clock,
            // so the inter-arrival gap stays ~0 and the next burst trips again.
            $store->set($ip, 'lock_until', $clock->now());
        }

        $this->assertSame([2, 4, 6], $retryAfters, 'Successive lockouts should escalate through lock_steps.');
        $this->assertSame([1, 2, 3], $levels, 'Lock level should increment on each repeat offense.');
    }

    private function floodGuard(string $ip, ClockInterface $clock, array $policy = []): Initiate
    {
        return new Initiate($policy, [
            'store' => $this->memoryStore(),
            'clock' => $clock,
            'server' => ['REMOTE_ADDR' => $ip],
        ]);
    }

    /** A hand-advanced clock so arrival times are exact and no test sleeps. */
    private function advancingClock(): ClockInterface
    {
        return new class() implements ClockInterface {
            private int $time = 1700000000;

            public function now(): int
            {
                return $this->time;
            }

            public function advance(int $seconds): void
            {
                $this->time += $seconds;
            }
        };
    }

    /** In-memory state store so the flood is isolated from sessions/Redis. */
    private function memoryStore(): StateStoreInterface
    {
        return new class() implements StateStoreInterface {
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
        };
    }
}


