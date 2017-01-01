<?php


namespace BrutalForce\Handler;

/**
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
interface ByInterface {
    
    
    public function inicializer();

    /**
     * 
     */
    public function isLocked();
    
    /**
     * 
     */
    public function unLock($boolean = false);
}
