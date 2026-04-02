<?php

namespace BrutalForce\Test\BrutalForce;

use PHPUnit\Framework\TestCase;
use BrutalForce\Initiate;

/**
 * Description of Initiate
 *
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class InitiateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $_SESSION = [];
    }

    public function testRateStartsVeryLow(): void
    {
        $init = new Initiate();
        $rate = $init->Rate();

        $this->assertSame(Initiate::RATES[0], $rate);
    }

    public function testPredictMovesAfterOneSecondDelay(): void
    {
        $init = new Initiate();
        $init->Rate();
        sleep(1);

        $prediction = $init->predict();

        $this->assertGreaterThan(0.0, $prediction);
    }

    public function testRateAlwaysReturnsKnownLabel(): void
    {
        $init = new Initiate();

        $init->Rate();
        $rate = $init->Rate();

        $this->assertContains($rate, Initiate::RATES);
    }
}
