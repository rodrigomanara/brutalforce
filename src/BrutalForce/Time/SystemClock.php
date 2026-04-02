<?php

declare(strict_types=1);

namespace BrutalForce\Time;

class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }
}

