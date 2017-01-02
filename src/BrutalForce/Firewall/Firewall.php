<?php

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\Holder;
use BrutalForce\Handler\byFile;

/**
 * Description of Firewall
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class Firewall extends Holder {

    private $lock = false;

    /**
     * 
     * @return type
     */
    public function isLocked() {
        return $this->lock;
    }
    /**
     * 
     * @param type $mixed
     */
    public function fileReadDecode() {
        $this->classLoader->fileReadDecode($this->path);
    }
    /**
     * 
     * @param type $type
     * @param type $forceUnlock
     * @return boolean
     */
    public function inicializer($type = self::TYPE_FILE, $forceUnlock = false) {
     
        switch ($type) {
            case 'byFile';
                $this->classLoader = new byFile($this->path);
                break;
        }
            
        if ($forceUnlock) {
            $this->unLock($forceUnlock);
        }

        if (is_callable(array($this->classLoader, 'isLocked'))) {
            return $this->classLoader->isLocked();
        }
        return false;
    }

    /**
     * 
     * @param type $boolean
     */
    public function unLock($boolean = false) {
        if ($boolean) {
            $this->classLoader->unLock(true);
        }
    }

}
