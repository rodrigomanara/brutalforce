<?php

namespace BrutalForce\Test;

use PHPUnit\Framework\TestCase;
use BrutalForce\Handler\byFile;

/**
 * Description of ByFileTest
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class ByFileTest extends TestCase {

    private $byfile;

    //put your code here
    protected function setUp() {
        $this->byfile = new byFile(__FIREWALL);
    }

    public function testFile() {
        
        $this->byfile->initializer();
        
        $array = $this->byfile->fileReadDecode();
        foreach ($array as $key) {
            $this->assertArrayHasKey('count', $key);
            $this->assertArrayHasKey('url', $key);
            $this->assertArrayHasKey('time', $key);
        }
    }

}
