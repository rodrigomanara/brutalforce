<?php


namespace BrutalForce\Component;

/**
 * Description of Check
 * <p> this method will look into time <br/> calculate how long from one request  <br/>
 * from another and hold the data </p>
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class CheckTime {

    /**
     * 
     * @return \DateTime
     */
    public function time() {
        return strtotime('now');
    }
    /**
     * 
     * @param type $time
     * @return type
     */
    public function timeDiff($time) {
        return $this->time() - $time ;
    }
    /**
     * 
     * @param type $time
     * @return type
     */
    public function unlockTime($time){
        $unlock = $this->timeDiff($time);
        return $unlock >= 600 ? true : false;
    }
    
}
