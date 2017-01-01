<?php

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\Holder;

/**
 * Description of Firewall
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class Firewall extends Holder {

    private $lock = false;

    /**
     * 
     * @param type $path
     */
    public function __construct($path , $unlock = false) {

        if ($this->init(Holder::TYPE_FILE, $path)) {
            $this->lock = true;
        }
        
        if($unlock){
            $this->forceUnlock($unlock);
            $this->lock = false;
        }
    }
    /**
     * 
     * @return type
     */
    public function isLocked() {
        return $this->lock;
    }

}
