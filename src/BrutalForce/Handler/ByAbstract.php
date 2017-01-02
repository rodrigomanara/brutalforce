<?php

namespace BrutalForce\Handler;

use Symfony\Component\HttpFoundation\Request;
use BrutalForce\Component\CheckTime;
use BrutalForce\FileManager\File;
use BrutalForce\Component\RequestWrapper;
use BrutalForce\Handler\ByInterface;
/**
 * Description of ByAbstract
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
abstract class ByAbstract extends CheckTime implements ByInterface , HandlerInterface {

    /**
     * 
     */
    CONST FIREWALL = "firewall" . DIRECTORY_SEPARATOR;

    /**
     *
     * @var RequestWrapper 
     */
    protected $request;

    /**
     *
     * @var type 
     */
    protected $root;

    /**
     *
     * @var file physical path 
     */
    protected $filePath;

    /**
     *
     * @var string request client IP  
     */
    protected $ip;

    /**
     * path is used to set the file path
     * @var type 
     */
    protected $path;

    /**
     *
     * @var File 
     */
    protected $file;


    /**
     * 
     * @param Request $request
     * @param string $path
     */
    public function __construct($path = '') {

        $this->file = new File();
        $this->request = new RequestWrapper();

        $this->root = $path;

        $this->ip = $this->request->getClientIp();
        $path = $this->root . DIRECTORY_SEPARATOR . self::FIREWALL;
        $this->path = $path;
        $this->filePath = $path . "{$this->ip}_locked.text";
        
        // start the class run
        $this->inicializer();
    }

}
