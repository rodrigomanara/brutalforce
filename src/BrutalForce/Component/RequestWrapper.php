<?php

namespace BrutalForce\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Description of Request
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class RequestWrapper extends Request{
    
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null) {
        parent::__construct($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, $content);
    }

     
}
