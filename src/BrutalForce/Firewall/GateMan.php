<?php

namespace BrutalForce\Firewall;

use BrutalForce\Cache\KeepinMemory;

class GateMan extends KeepinMemory
{
    private const LEARNING_WINDOW = 60;

    protected function security(): void
    {
        $unix = time();
        $initial = self::getSession('initial');

        if (!is_int($initial)) {
            $this->initial($unix);
            $this->setLearning(0);

            return;
        }

        // Track the request cadence per client IP.
        $this->setLearning(self::timediff($initial, $unix));
        $this->initial($unix);
    }
    /**
     *
     * @param string $session
     * @param int $unix
     * @return int
     */
    private static function timediff(int $timea, int $timeb): int
    {
        $total = $timeb > $timea
                ? $timeb - $timea : $timea - $timeb;

        return $total;
    }

    /**
     *
     * @return boolean
     */
    private function initial(int $unix): void
    {
        self::setSession('initial', $unix);
    }
    /**
     * Undocumented function
     *
     * @param integer $data
     * @return void
     */
    private static function setLearning(int $data): void
    {
        $learning = self::getSession('learning');
        if (!is_array($learning)) {
            $learning = [];
        }

        $learning[] = $data;
        if (count($learning) > self::LEARNING_WINDOW) {
            $learning = array_slice($learning, -self::LEARNING_WINDOW);
        }

        self::setSession('learning', $learning);
    }
    
    /**
     * Undocumented function
     *
     * @return array|null
     */
    protected static function getLearning(): array
    {
        $learn = self::getSession('learning');

        return is_array($learn) ? $learn : [];
    }
}
