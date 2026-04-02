<?php

namespace BrutalForce\Test\BrutalForce;

use BrutalForce\Initiate;
use PHPUnit\Framework\TestCase;

class SimulationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '192.168.0.2';
        $_SESSION = [];
    }

    public function testBurstSimulationRemainsInLowBand(): void
    {
        $init = new Initiate();
        $predictions = [];

        for ($i = 0; $i < 30; $i++) {
            $predictions[] = $init->predict();
            usleep(1000);
        }

        $this->assertLessThanOrEqual(0.5, max($predictions));
        $this->assertContains($init->Rate(), [Initiate::RATES[0], Initiate::RATES[1]]);
    }

    public function testSimulatedOneSecondCadenceMapsToLow(): void
    {
        $scoreRun = $this->seedCadenceState(1);
        $this->assertEqualsWithDelta(0.5, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(1);
        $this->assertSame(Initiate::RATES[1], $rateRun->Rate());
    }

    public function testSimulatedTwoSecondCadenceMapsToMediumHigh(): void
    {
        $scoreRun = $this->seedCadenceState(2);
        $this->assertEqualsWithDelta(1.0, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(2);
        $this->assertSame(Initiate::RATES[3], $rateRun->Rate());
    }

    public function testSimulatedThreeSecondCadenceMapsToVeryHigh(): void
    {
        $scoreRun = $this->seedCadenceState(3);
        $this->assertEqualsWithDelta(1.5, $scoreRun->predict(), 0.001);

        $rateRun = $this->seedCadenceState(3);
        $this->assertSame(Initiate::RATES[5], $rateRun->Rate());
    }

    private function seedCadenceState(int $secondsBetweenRequests): Initiate
    {
        $init = new Initiate();
        $ip = $_SERVER['REMOTE_ADDR'];

        $_SESSION['brutalforce.v1'][$ip]['learning'] = [0];
        $_SESSION['brutalforce.v1'][$ip]['initial'] = time() - $secondsBetweenRequests;

        return $init;
    }
}


