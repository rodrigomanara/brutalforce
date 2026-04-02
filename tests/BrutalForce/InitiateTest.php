<?php

namespace BrutalForce\Test\BrutalForce;

use BrutalForce\Time\ClockInterface;
use PHPUnit\Framework\TestCase;
use BrutalForce\Initiate;

/**
 * Description of Initiate
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class InitiateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $_SESSION = [];
    }

    public function testRateStartsVeryLow(): void
    {
        $init = new Initiate();
        $rate = $init->Rate();

        $this->assertSame(Initiate::RATES[0], $rate);
    }

    public function testPredictMovesAfterOneSecondDelay(): void
    {
        $init = new Initiate();
        $init->Rate();
        sleep(1);

        $prediction = $init->predict();

        $this->assertGreaterThan(0.0, $prediction);
    }

    public function testRateAlwaysReturnsKnownLabel(): void
    {
        $init = new Initiate();

        $init->Rate();
        $rate = $init->Rate();

        $this->assertContains($rate, Initiate::RATES);
    }

    public function testDecisionReturnsExpectedPayload(): void
    {
        $init = new Initiate();
        $decision = $init->decision();

        $this->assertArrayHasKey('blocked', $decision);
        $this->assertArrayHasKey('score', $decision);
        $this->assertArrayHasKey('rate', $decision);
        $this->assertArrayHasKey('retry_after', $decision);
        $this->assertArrayHasKey('violations', $decision);
        $this->assertArrayHasKey('lock_level', $decision);
        $this->assertArrayHasKey('policy', $decision);
    }

    public function testBlockIsActivatedAfterRepeatedSuspiciousCadence(): void
    {
        $init = new Initiate();
        $decision = [];

        for ($i = 0; $i < 8; $i++) {
            $decision = $init->decision();
            usleep(1000);

            if ($decision['blocked']) {
                break;
            }
        }

        $this->assertTrue($decision['blocked']);
        $this->assertGreaterThan(0, $decision['retry_after']);
        $this->assertTrue($init->isBlocked());
    }

    public function testLockStateCanBeReadFromSession(): void
    {
        $init = new Initiate();
        $ip = $_SERVER['REMOTE_ADDR'];
        $_SESSION['brutalforce.v1'][$ip]['lock_until'] = time() + 20;

        $this->assertTrue($init->isBlocked());
        $this->assertGreaterThanOrEqual(1, $init->lockRemaining());
    }

    public function testPolicyCanBeConfiguredFromConstructor(): void
    {
        $init = new Initiate([
            'threshold' => 0.4,
            'violation_limit' => 2,
            'lock_steps' => [9, 18],
        ]);

        $policy = $init->policy();
        $this->assertSame(0.4, $policy['threshold']);
        $this->assertSame(2, $policy['violation_limit']);
        $this->assertSame([9, 18], $policy['lock_steps']);

        $first = $init->decision();
        $second = $init->decision();

        $this->assertFalse($first['blocked']);
        $this->assertTrue($second['blocked']);
        $this->assertLessThanOrEqual(9, $second['retry_after']);
        $this->assertSame(2, $second['policy']['violation_limit']);
        $this->assertSame($policy, $second['policy']);
    }

    public function testProgressiveLockoutsIncreaseForRepeatedOffender(): void
    {
        $init = new Initiate([
            'threshold' => 0.5,
            'violation_limit' => 1,
            'lock_steps' => [2, 4, 6],
        ]);

        $ip = $_SERVER['REMOTE_ADDR'];

        $lock1 = $init->decision();
        $this->assertTrue($lock1['blocked']);
        $this->assertLessThanOrEqual(2, $lock1['retry_after']);

        $_SESSION['brutalforce.v1'][$ip]['lock_until'] = time() - 1;
        $lock2 = $init->decision();
        $this->assertTrue($lock2['blocked']);
        $this->assertLessThanOrEqual(4, $lock2['retry_after']);

        $_SESSION['brutalforce.v1'][$ip]['lock_until'] = time() - 1;
        $lock3 = $init->decision();
        $this->assertTrue($lock3['blocked']);
        $this->assertLessThanOrEqual(6, $lock3['retry_after']);

        $this->assertSame(1, $lock1['lock_level']);
        $this->assertSame(2, $lock2['lock_level']);
        $this->assertSame(3, $lock3['lock_level']);
    }

    public function testPolicyIsInstanceScoped(): void
    {
        $tight = new Initiate(['violation_limit' => 1]);
        $default = new Initiate();

        $this->assertSame(1, $tight->policy()['violation_limit']);
        $this->assertSame(5, $default->policy()['violation_limit']);
    }

    public function testTrustedProxyCanResolveForwardedClientIp(): void
    {
        $guard = new Initiate([], [
            'trusted_proxies' => ['127.0.0.1'],
            'server' => [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.10, 127.0.0.1',
            ],
        ]);

        $guard->Rate();

        $this->assertArrayHasKey('203.0.113.10', $_SESSION['brutalforce.v1']);
    }

    public function testCustomClockMakesTimeDeterministic(): void
    {
        $clock = new class() implements ClockInterface {
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

        $guard = new Initiate([], ['clock' => $clock]);
        $guard->Rate();
        $clock->advance(2);

        $this->assertEqualsWithDelta(1.0, $guard->predict(), 0.001);
    }

    public function testRedisBackendOptionCanBeUsed(): void
    {
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

        $guard = new Initiate([], ['redis' => $redis]);
        $decision = $guard->decision();

        $this->assertIsArray($decision);
        $this->assertNotEmpty($redis->rows);
    }

    public function testRedisFactoryHelperCanBeUsed(): void
    {
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

        $guard = Initiate::withRedis($redis, ['violation_limit' => 1]);
        $decision = $guard->decision();

        $this->assertSame(1, $decision['policy']['violation_limit']);
        $this->assertNotEmpty($redis->rows);
    }
}
