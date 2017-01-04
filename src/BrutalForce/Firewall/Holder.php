<?php

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\interfaceFirewall;
use BrutalForce\Handler\ByInterface;
use ReCaptcha\ReCaptcha;
use \BrutalForce\Component\RequestWrapper;

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
     * @var type 
     */
    private $siteKey;

    /**
     *
     * @var type 
     */
    private $secret;

    /**
     *
     * @var ReCaptcha 
     */
    protected $recaptch;

    /**
     *
     * @var RequestWrapper 
     */
    protected $request;

    /**
     * 
     * @param type $path
     * @param type $siteKey
     * @param type $secret
     */
    public function __construct($path = __DIR__, $siteKey = '', $secret = '') {
        $this->path = $path;
        $this->siteKey = $siteKey;
        $this->secret = $secret;
        $this->request = new RequestWrapper();
    }

    /**
     * 
     * @return type
     */
    protected  function getCaptchaForm() {
        $lang = $this->request->getLocale();
        $form = "<div class=\"g-recaptcha\" data-sitekey=\"{$this->siteKey}\"></div><script type=\"text/javascript\" src=\"https://www.google.com/recaptcha/api.js?hl={ $lang }\"></script>";
        return $form;
    }

    /**
     * 
     * @return boolean
     * @throws Exception
     */
    protected function recaptcha() {

        if ($this->request->isMethod('post') && !is_null($this->siteKey) && !is_null($this->secret)) {

            $this->recaptch = new ReCaptcha($this->secret);
            $response = $this->recaptch->verify($this->request->get('g-recaptcha-response'), $this->request->getClientIp());

            if ($response->isSuccess()) {
                $this->unLock(true);
                return $this->isLocked();
            } else {
                throw new Exception("The following error was returned" . $response->getErrorCodes());
            }
        } else {
            throw new Exception('SiteKey and Secret is null');
        }
    }

}
