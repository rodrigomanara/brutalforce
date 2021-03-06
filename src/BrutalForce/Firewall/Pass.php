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

        $sum = array_sum($predict);
        $count = count($predict);
 
        $predicting =  ($sum == 0 ? 0 : ($count == 0 ? 0 : $sum /$count)) ;
        $total =  round($predicting , 3, PHP_ROUND_HALF_EVEN);
        
        return $total;
    }
    
    
    
    
}
