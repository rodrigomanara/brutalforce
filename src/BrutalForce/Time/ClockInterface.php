<?php

declare(strict_types=1);

namespace BrutalForce\Time;

interface ClockInterface
{
    public function now(): int;
}

