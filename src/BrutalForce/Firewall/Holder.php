<?php

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\interfaceFirewall;
use BrutalForce\Handler\ByInterface;

/**
 * 
 */
Abstract class Holder implements interfaceFirewall, ByInterface {

    /**
     *
     * @var ByInterface 
     */
    protected $classLoader;

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
    /**
     * 
     * @param type $path
     */
    public function __construct($path = __DIR__) {
        $this->path = $path;
    }

}
