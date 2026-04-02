<?php
 
namespace BrutalForce\Firewall;

use BrutalForce\Firewall\GateMan;

/**
 *
 */
abstract class Pass extends GateMan
{

    public function predict(): float
    {
        // Run checks before reading the latest learning window.
        $this->security();

        $predict = $this->getLearning();
        if ($predict === []) {
            return 0.0;
        }

        $sum = array_sum($predict);
        $count = count($predict);

        $predicting = $count === 0 ? 0.0 : ($sum / $count);
        $total = round($predicting, 3, PHP_ROUND_HALF_EVEN);

        return $total;
    }
}
