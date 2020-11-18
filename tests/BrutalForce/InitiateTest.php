<?php

namespace BrutalForce\Test;

use PHPUnit\Framework\TestCase;
use BrutalForce\Initiate;
use BrutalForce\Request\Call;

/**
 * Description of Initiate
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class InitiateTest extends TestCase
{

    const holder = 'TESTHOLDER';

    /**
     * 
     * @return void
     */
    public function testRatio(): void
    {

        $test0 = [
            Call::threshold(0.0),
            Call::threshold(0.1),
            Call::threshold(0.2),
            Call::threshold(0.3),
            Call::threshold(0.4)
        ];

        foreach ($test0 as $test) {
            $this->assertFalse($test, '');
        }

        $test1 = [
            Call::threshold(0.5),
            Call::threshold(0.6),
            Call::threshold(0.7),
            Call::threshold(0.8),
            Call::threshold(0.9),
            Call::threshold(1.0)
        ];

        foreach ($test1 as $test) {
            $this->assertTrue($test);
        }
    }

    /**
     * 
     * @return void
     */
    public function testJS(): void
    {

        $js = new Initiate('test1', 'test2');
        $this->assertIsString($js->getScript());
        $this->assertIsString($js->getUrl());
    }
    /**
     * 
     * @return void
     */
    public function testSession(): void
    {
        if (!isset( $_SESSION ))
            $_SESSION = [];

        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $ip = $_SERVER['REMOTE_ADDR'];

        
        $s = new Initiate('test1', 'test2');
        $s->run();
        
        //session
        $this->assertIsBool(isset($_SESSION[$ip]['wellcome']));
    }

}
