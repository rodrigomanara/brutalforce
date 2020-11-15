<?php

namespace BrutalForce\Request;

/**
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
abstract class Call
{

    const URL = 'https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s';
    const THRESHOLD = 0.5;

    /**
     *
     * @param string $secret
     * @param string $token
     */
    public static function verifiy(string $secret, string $token): bool
    {

        $url = sprintf(self::URL, urlencode($secret), urlencode($token));

        $response = file_get_contents($url);
        $responsedecode = json_decode($response, true);
        header('Content-type: application/json');
        if($responsedecode["success"] && self::threshold($responsedecode['score']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     *
     * @param mixed $threshold
     * @return boolean
     */
    private static function threshold($threshold)
    {

        if(self::THRESHOLD < $threshold)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

}
