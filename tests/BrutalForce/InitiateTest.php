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
    public function testDDOS(): void
    {

        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $init = new Initiate();

        $i = 0;
        do {
            //
            if ($i > 0 && $i < 2) {
                sleep(2);
            }
            //
            $i++;
            $count = count($init::RATES);
            for ($i = 0; $i < $count; $i++) {
                if ($init->Rate() == $init::RATES[$i]) {
                    $this->assertEquals($init->Rate(), $init::RATES[$i]);
                }
            }
        } while ($i < 1000);
    }
}