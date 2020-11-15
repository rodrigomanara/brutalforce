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

    /**
     * 
     * @return void
     */
    public function testJSFile(): void
    {

        $sub = $this->createMock(Initiate::class);
        
    }

    /**
     * 
     * @return void
     */
    public function testSession(): void
    {
        //
        $ip = $_SERVER['REMOTE_ADDR'];
        //
        $this->assertIsBool(isset($_SESSION[$ip]['wellcome']));
        $this->assertIsBool(isset($_SESSION[$ip]['youagain']));
        $this->assertIsBool(isset($_SESSION[$ip]['toosoon']));
        $this->assertIsBool(isset($_SESSION[$ip]['comebacklater']));
    }

}
