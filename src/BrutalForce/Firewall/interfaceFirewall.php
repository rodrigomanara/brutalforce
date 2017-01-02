<?php


namespace BrutalForce\Firewall;

/**
 * Description of interfaceFirewall
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
interface interfaceFirewall {
    
    ## set constant for types of method to use

    CONST TYPE_FILE = "byFile";
    /**
     * 
     * @param type $type
     * @param type $forceUnlock
     */
    function initializer($type = self::TYPE_FILE, $forceUnlock = false);
}
