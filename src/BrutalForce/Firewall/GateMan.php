<?php

namespace BrutalForce\Firewall;

use BrutalForce\Cache\KeepinMemory;

class GateMan extends KeepinMemory
{

    protected function security()
    {

        $time = new \DateTime();
        $unix = $time->getTimestamp();

        if($this->wellcome())
        {
            self::setSession('wellcome', $unix);
        }
        elseif($this->youagain())
        {
            $diff = self::timediff(self::getSession('wellcome'), $unix);
            if($diff->m == 0 && $diff->s < 1)
            {
                self::setSession('youagain', $unix);
            }
        }
        elseif($this->toosoon())
        {
            $diff = self::timediff(self::getSession('youagain'), $unix);
            if($diff->m == 0 && $diff->s < 1)
            {
                self::setSession('toosoon', $unix);
            }
        }
        elseif($this->comebacklater())
        {
            $diff = self::timediff(self::getSession('toosoon'), $unix);
            if($diff->m == 0 && $diff->s < 1)
            {
                self::setSession('comebacklater', $unix);
            }
        }
        else
        {

            $diff = self::timediff(self::getSession('comebacklater'), $unix);
            if($diff->m > 1 && $diff->s > 0)
            {
                //clean all
                $this->reset();
                return true;
            }
            //not allowed now
            return false;
        }

        return true;
    }

    /**
     *
     * @return void
     */
    private function reset(): void
    {

        $list = ['wellcome', 'youagain', 'toosoon', 'comebacklater'];
        foreach($list as $session)
        {
            self::UnsetSession($session);
        }
    }

    /**
     *
     * @param mixed $timea
     * @param mixed $timeb
     * @return DateTime
     */
    private static function timediff($timea, $timeb)
    {
        $compare = new \DateTime($timea);
        $compareb = new \DateTime($timeb);

        $diff = $compare->diff($compareb);
        return $diff;
    }

    /**
     *
     * @return boolean
     */
    private function wellcome()
    {
        if(self::getSession('wellcome'))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     *
     * @return boolean
     */
    private function youagain()
    {
        if(self::getSession('youagain'))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     *
     * @return boolean
     */
    private function toosoon()
    {
        if(self::getSession('toosoon'))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     *
     * @return boolean
     */
    private function comebacklater()
    {
        if(self::getSession('comebacklater'))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

}
