<?php

namespace BrutalForce\Firewall;

use BrutalForce\Cache\KeepinMemory;
use BrutalForce\Cache\StateStoreInterface;
use BrutalForce\Time\ClockInterface;
use BrutalForce\Time\SystemClock;

class GateMan extends KeepinMemory
{
    private const LEARNING_WINDOW = 60;
    protected const BLOCK_SCORE_THRESHOLD = 0.5;
    protected const VIOLATION_LIMIT = 5;
    protected const LOCKOUT_SECONDS = 60;
    protected const LOCKOUT_STEPS = [60, 120, 300];

    protected ClockInterface $clock;
    /** @var array{threshold:float,violation_limit:int,lock_steps:array<int,int>} */
    protected array $policy;

    public function __construct(StateStoreInterface $store, string $clientKey, ?ClockInterface $clock = null, array $policy = [])
    {
        parent::__construct($store, $clientKey);
        $this->clock = $clock ?? new SystemClock();
        $this->policy = $this->resolvePolicy($policy);
    }

    protected function security(): void
    {
        $unix = $this->clock->now();
        $initial = $this->getSession('initial');

        if (!is_int($initial)) {
            $this->initial($unix);
            $this->setLearning(0);

            return;
        }

        // Track the request cadence per client IP.
        $this->setLearning($this->timediff($initial, $unix));
        $this->initial($unix);
    }

    private function timediff(int $timea, int $timeb): int
    {
        $total = $timeb > $timea
                ? $timeb - $timea : $timea - $timeb;

        return $total;
    }

    private function initial(int $unix): void
    {
        $this->setSession('initial', $unix);
    }

    private function setLearning(int $data): void
    {
        $learning = $this->getSession('learning');
        if (!is_array($learning)) {
            $learning = [];
        }

        $learning[] = $data;
        if (count($learning) > self::LEARNING_WINDOW) {
            $learning = array_slice($learning, -self::LEARNING_WINDOW);
        }

        $this->setSession('learning', $learning);
    }

    protected function getLearning(): array
    {
        $learn = $this->getSession('learning');

        return is_array($learn) ? $learn : [];
    }

    protected function evaluateProtection(float $score): void
    {
        if ($this->isLockActive()) {
            return;
        }

        $violations = $this->getViolations();
        $threshold = $this->getThreshold();

        if ($score <= $threshold) {
            $violations++;
        } elseif ($violations > 0) {
            // Decay violations on safer cadence to avoid sticky lockouts.
            $violations--;
        }

        if ($violations >= $this->getViolationLimit()) {
            $lockLevel = $this->getLockLevel() + 1;
            $steps = $this->getLockSteps();
            $duration = $steps[min($lockLevel - 1, count($steps) - 1)];

            $this->setSession('lock_level', $lockLevel);
            $this->setSession('lock_until', $this->clock->now() + $duration);
            $violations = 0;
        }

        $this->setSession('violations', $violations);
    }

    protected function isLockActive(): bool
    {
        $lockUntil = $this->getSession('lock_until');
        if (!is_int($lockUntil)) {
            return false;
        }

        if ($lockUntil <= $this->clock->now()) {
            $this->unsetSession('lock_until');

            return false;
        }

        return true;
    }

    protected function getLockRemaining(): int
    {
        if (!$this->isLockActive()) {
            return 0;
        }

        $lockUntil = $this->getSession('lock_until');

        return is_int($lockUntil) ? max(0, $lockUntil - $this->clock->now()) : 0;
    }

    protected function getViolations(): int
    {
        $violations = $this->getSession('violations');

        return is_int($violations) ? $violations : 0;
    }

    protected function getLockLevel(): int
    {
        $level = $this->getSession('lock_level');

        return is_int($level) ? max(0, $level) : 0;
    }

    protected function getPolicy(): array
    {
        return $this->policy;
    }

    private function getThreshold(): float
    {
        return $this->policy['threshold'];
    }

    private function getViolationLimit(): int
    {
        return $this->policy['violation_limit'];
    }

    private function getLockSteps(): array
    {
        return $this->policy['lock_steps'];
    }

    /**
     * Constructor values override env values, then defaults are applied.
     *
     * @return array{threshold:float,violation_limit:int,lock_steps:array<int,int>}
     */
    private function resolvePolicy(array $policy): array
    {
        $threshold = $policy['threshold'] ?? getenv('BRUTALFORCE_THRESHOLD');
        $violationLimit = $policy['violation_limit'] ?? getenv('BRUTALFORCE_VIOLATIONS');
        $stepsRaw = $policy['lock_steps'] ?? null;

        if (!is_array($stepsRaw)) {
            $fromEnvSteps = getenv('BRUTALFORCE_LOCK_STEPS');
            if (is_string($fromEnvSteps) && trim($fromEnvSteps) !== '') {
                $stepsRaw = explode(',', $fromEnvSteps);
            }
        }

        if (!is_array($stepsRaw)) {
            $single = getenv('BRUTALFORCE_LOCK_SECONDS');
            if (is_string($single) && is_numeric($single) && (int) $single > 0) {
                $stepsRaw = [(int) $single];
            }
        }

        $steps = is_array($stepsRaw)
            ? array_values(array_filter(array_map('intval', $stepsRaw), static fn (int $v): bool => $v > 0))
            : [];

        return [
            'threshold' => is_numeric($threshold) ? max(0.0, (float) $threshold) : self::BLOCK_SCORE_THRESHOLD,
            'violation_limit' => is_numeric($violationLimit) ? max(1, (int) $violationLimit) : self::VIOLATION_LIMIT,
            'lock_steps' => $steps !== [] ? $steps : (self::LOCKOUT_STEPS !== [] ? self::LOCKOUT_STEPS : [self::LOCKOUT_SECONDS]),
        ];
    }
}
