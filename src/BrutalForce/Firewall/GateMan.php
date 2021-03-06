<?php

namespace BrutalForce\Firewall;

use BrutalForce\Cache\KeepinMemory;

class GateMan extends KeepinMemory
{
    protected function security()
    {
        $time = new \DateTime();
        $unix = $time->getTimestamp();
        //
        if (!self::getSession('initial')) {
            $this->initial();
        }
        //set the counter
        $this->setLearning($this->diff('initial' , $unix));     
        //set new counter
        $this->initial();
    }
    /**
     *
     * @param string $session
     * @param int $unix
     * @return int
     */
    private static function diff(string $session, int $unix)
    {
        return self::timediff(self::getSession($session), $unix);
    }

    /**
     *
     * @param mixed $timea
     * @param mixed $timeb
     * @return DateTime
     */
    private static function timediff($timea, $timeb)
    {
        $total = $timeb > $timea
                ? $timeb - $timea : $timea - $timeb;

        return $total;
    }

    /**
     *
     * @return boolean
     */
    private function initial()
    {
        $time = new \DateTime();
        $unix = $time->getTimestamp();
        self::setSession('initial', $unix);
    }
    /**
     * Undocumented function
     *
     * @param integer $data
     * @return void
     */
    private static function setLearning(int $data){
        $_SESSION['learning'][] = $data;
    }
    
    /**
     * Undocumented function
     *
     * @return array|null
     */
    protected static function getLearning() :?array  {
        $learn = self::getSession('learning');
        return $learn;
    }
    
    /**
     * 
     */
    public function __destruct()
    {
        $_SESSION['learning'] = null;
    }
}
