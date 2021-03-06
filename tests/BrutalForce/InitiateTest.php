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
    public function testLowRate(): void
    {
        $init = new Initiate();

        for ($i = 0 ; $i < 10000 ;$i++) {
            //
            if ($i % 5 == 0) {
                usleep(500);
            }

            $rate = $init->Rate();

            if (isset($init::RATES[$i]) && $rate == $init::RATES[$i]) {
                $this->assertEquals($rate, $init::RATES[$i],'Low Rate Test');
            }

            if (isset($init::RATES[$i]) && $rate !== $init::RATES[$i]) {
                $this->assertNotEquals($rate, $init::RATES[$i], 'Low Rates if is not same');
            }
        }
    }

    public function testAboveLow(): void
    {
        $init = new Initiate();


        for ($i = 0; $i < 10 ;$i++) {
            //
            if (sleep(2) == 0) {
                $rate = $init->Rate() ;
                if ($rate== $init::RATES[2]) {
                    $this->assertEquals($rate, $init::RATES[2]);
                }
                if ($rate == $init::RATES[3]) {
                    $this->assertEquals($rate, $init::RATES[3]);
                }
                if ($rate == $init::RATES[4]) {
                    $this->assertEquals($rate, $init::RATES[4]);
                }
                if ($rate == $init::RATES[5]) {
                    $this->assertEquals($rate, $init::RATES[5]);
                }
            }
        }
    }
}
