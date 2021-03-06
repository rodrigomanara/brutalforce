<?php
 
namespace BrutalForce\Firewall;

use BrutalForce\Firewall\GateMan;

/**
 *
 */
abstract class Pass extends GateMan
{
    
    public function predict()
    {
        //run checks
        $this->security();
        //
        $predict = static::getLearning();
        $predicting = array_sum($predict) / count($predict);
        $total =  round($predicting , 3, PHP_ROUND_HALF_EVEN);
        
        return $total;
    }
    
    
    
    
}
