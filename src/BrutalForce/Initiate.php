<?php

namespace BrutalForce;

use BrutalForce\Firewall\Pass;

class Initiate extends Pass
{

    CONST RATES = [
        0 => 'VERY LOW',
        1 => 'LOW',
        2 => 'MEDIUM',
        3 => 'MEDIUM HIGH',
        4 => 'HIGH',
        5 => 'VERY HIGH',
    ];
    /**
     * 
     * @return type
     */
    public function Rate()
    {

        $p = $this->predict();
        
        if($p == 0){$rate = static::RATES[0];}
        if($p > 0 && $p <= 0.5){$rate = static::RATES[1];}
        if($p > 0.5 && $p <= 0.8){$rate = static::RATES[2];}
        if($p > 0.8 && $p <= 1){$rate = static::RATES[3];}
        if($p > 1 && $p <= 1.5){$rate = static::RATES[4];}
        if($p > 1.5){$rate = static::RATES[5];}
        
        return $rate;
    }

}
