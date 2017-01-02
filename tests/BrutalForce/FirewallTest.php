<?php

namespace BrutalForce\Test;

use PHPUnit\Framework\TestCase;
use BrutalForce\Firewall\Firewall;

/**
 * Description of testFirewall
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class FirewallTest extends TestCase {

    //put your code here

    private $brutal;

    protected function setUp() {
        $this->brutal = new Firewall(__FIREWALL);
    }

    /**
     * 
     */
    public function testInicializer() {
        $this->brutal->inicializer(Firewall::TYPE_FILE);
        $this->assertDirectoryExists(__FIREWALL . "/firewall");
    }
    /**
     * 
     */
    public function testIsLocked() {

        for ($i = 0; $i < 4; $i++) {
            $this->brutal->inicializer(Firewall::TYPE_FILE);
        }

        $this->assertTrue($this->brutal->isLocked());
    }
    /**
     * test unlock
     */
    public function testUnlocked() {
        for ($i = 0; $i < 4; $i++) {
            $this->brutal->inicializer(Firewall::TYPE_FILE);
        }
        //force to unlock
        $this->brutal->unLock(true);
        $this->assertFalse($this->brutal->isLocked());
        
    }

}
