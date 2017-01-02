<?php


namespace BrutalForce\Handler;

/**
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
interface ByInterface {
    
    /**
     * check if the function is locked
     */
    public function isLocked();
    
    /**
     * unlock the value the file
     */
    public function unLock($boolean = false);
    
    
    /**
     * 
     * @param path|name $mixed
     */
    public function fileReadDecode();
}
