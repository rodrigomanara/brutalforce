<?php

namespace BrutalForce\FileManager;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Description of File
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class File {

    /**
     *
     * @var type 
     */
    public $fs;

    /**
     * 
     */
    public function __construct() {
        $this->fs = new Filesystem();
    }

    /**
     * 
     * @param type $path
     */
    public function create($path) {

        try {
            $this->fs->mkdir($path);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at " . $e->getPath();
        }
    }

    public function writeContent($filename, $content , $type = "a" ) {

        if (file_exists($filename)) {
            $handle = fopen($filename, $type);
            fwrite($handle, $content);
            fclose($handle);
        }else{
            throw new \Exception('file was not created');
        }
    }
 
    
}
