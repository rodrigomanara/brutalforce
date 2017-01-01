<?php

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\interfaceFirewall;
use BrutalForce\Component\RequestWrapper;
use BrutalForce\Handler\byFile;

/**
 * 
 */
Abstract class Holder implements interfaceFirewall {

    /**
     *
     * @var type 
     */
    protected $path;

    /**
     *
     * @var type 
     */
    protected $session;

    /**
     *
     * @var type 
     */
    protected $file;

    /**
     *
     * @var array 
     */
    protected $lockedDetails = array();


    ## set constant for types of method to use

    CONST TYPE_FILE = "byFile";
    CONST TYPE_SESSION = "bySession";

    /**
     * 
     * @param type $type
     * @param type $path
     * @return type
     */
    protected function init($type = self::TYPE_FILE, $path = __DIR__) {
        $request = new RequestWrapper();

        $class = '';
        switch ($type) {
            case 'byFile';
                $class = new byFile($request, $path);
                
                break;
        }

        if($this->lock){
            $class->unLock(true);
        }

        if (is_callable(array($class, 'isLocked'))) {
            return $class->isLocked();
        }
        return false;
    }
    

    /**
     * 
     * @param type $lock
     */
    public function forceUnlock($lock = false){
        $this->lock = $lock;
    }
    
    /**
     * 
     */
    public function lockedDetails() {
        
    }
    
}
