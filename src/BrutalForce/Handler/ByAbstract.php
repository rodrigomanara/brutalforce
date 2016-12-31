<?php

namespace BrutalForce\Handler;

use \Symfony\Component\HttpFoundation\Request;
use BrutalForce\Component\CheckTime;

/**
 * Description of ByAbstract
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class ByAbstract extends CheckTime {

    /**
     * 
     */
    CONST FIREWALL = "firewall" . DIRECTORY_SEPARATOR;

    /**
     *
     * @var type 
     */
    protected $request;

    /**
     *
     * @var type 
     */
    protected $root;

    public function __construct(Request $request, $path = '') {
        $this->request = $request;
        $this->root = $path;
    }

}
