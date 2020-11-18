<?php

namespace BrutalForce;

use BrutalForce\Firewall\Pass;

class Initiate extends Pass
{

    /**
     *
     * @return boolean
     * @throws Exception
     */
    public function run()
    {

        $pass = $this->shouldI();
        if(!$pass)
        {
            //post request to send collect the token
            $token = $this->getToken();
            if(!$token)
            {
                throw new Exception("Token is null, please check you current setup!!!");
            }

            return $this->shouldI($token);
        }

        return $pass;
    }

}
