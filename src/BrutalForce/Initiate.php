<?php

namespace BrutalForce;

use BrutalForce\Firewall\Pass;

class Initiate extends Pass
{

    public const RATES = [
        0 => 'VERY LOW',
        1 => 'LOW',
        2 => 'MEDIUM',
        3 => 'MEDIUM HIGH',
        4 => 'HIGH',
        5 => 'VERY HIGH',
    ];
    /**
     *
     * @return string
     */
    public function Rate(): string
    {
        $p = $this->predict();

        return match (true) {
            $p === 0.0 => static::RATES[0],
            $p > 0.0 && $p <= 0.5 => static::RATES[1],
            $p > 0.5 && $p <= 0.8 => static::RATES[2],
            $p > 0.8 && $p <= 1.0 => static::RATES[3],
            $p > 1.0 && $p <= 1.2 => static::RATES[4],
            default => static::RATES[5],
        };
    }

}
