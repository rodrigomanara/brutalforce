<?php

namespace BrutalForce\Test;

use PHPUnit\Framework\TestCase;
use BrutalForce\Initiate;

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
    public function testJSScript(): void
    {
        $js = (new Initiate(self::holder, self::holder))->getScript();
        $this->assertIsString($js);
    }

    /**
     * 
     * @return void
     */
    public function testJSUrl(): void
    {
        $this->assertIsString((new Initiate(self::holder, self::holder))->getUrl());
    }

    /**
     * 
     * @return void
     */
    public function testSession(): void
    {
        
        $session = (new Initiate(self::holder, self::holder));
        //
        $_SERVER['REMOTE_ADDR'] =  '192.168.1.1';
        $ip = $_SERVER['REMOTE_ADDR'];
        //
        $this->assertIsBool(isset($_SESSION[$ip]['wellcome']));
        $this->assertIsBool(isset($_SESSION[$ip]['youagain']));
        $this->assertIsBool(isset($_SESSION[$ip]['toosoon']));
        $this->assertIsBool(isset($_SESSION[$ip]['comebacklater']));
    }

}
