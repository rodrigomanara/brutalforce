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

    /**
     * will have response from recaptchar
     * <br/> array("valid" => boolean, <br/> "form_message" => string <br/>, "form" => string <br/>);
     * @var mixed 
     */
    public $recaptcha;

    /**
     * 
     * @return type
     */
    public function isLocked() {
        return $this->classLoader->isLocked();
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
    public function initializer($type = self::TYPE_FILE, $forceUnlock = false) {

        switch ($type) {
            case 'byFile';
                $this->classLoader = new byFile($this->path);
                break;
        }

        if ($forceUnlock) {
            $this->unLock($forceUnlock);
        }
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

    /**
     * 
     * @return type
     * @throws Exception
     */
    public function verify() {
        try {

            if ($this->request->isMethod('post') && $this->isLocked()) {
                $this->recaptcha = $this->callRecaptcha();
            } elseif ($this->request->isMethod('get') && $this->isLocked()) {
                $this->recaptcha = $this->getCaptchaForm();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $this;
    }

}
