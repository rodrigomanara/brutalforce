<?php

namespace BrutalForce\Handler;



use BrutalForce\Handler\ByAbstract;

/**
 * Description of ByFile
 *  
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 */
class byFile extends ByAbstract {

   

    /**
     * open the file and read the content
     * @param type $file_path
     * @param type $decode
     * @param type $ip
     */
    private function _stage_two($file_path, $decode) {

        $data = $this->fileReadDecode($file_path);

        if (isset($data[$this->ip])) {
            $decode[$this->ip]['time'] = $data[$this->ip]['time'];
            $count = $data[$this->ip]['count'];
            $decode = $this->setLock($data, $decode, $count);
        }

        return $decode;
    }

    
    /**
     * 
     * @param type $file_path
     * @return type
     */
    public function fileReadDecode() {
        
        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);
        return $data;
    }

    /**
     * start the system to compile the file
     */
    public function inicializer() {
       // start 
        $decode = array();

        $decode[$this->ip] = array(
            'count' => 0,
            'locked' => false,
            'url' => $this->request->getRequestUri(),
            'time' => $this->time()
        );

        if (!is_dir($this->path)) {
            $this->file->create($this->path);
        } else if (!is_file($this->filePath)) {
            $this->file->fs->touch($this->filePath);
        } else if (is_file($this->filePath)) {
            $decode = $this->_stage_two($this->filePath, $decode);
        }

        $this->file->writeContent($this->filePath, json_encode($decode), "w");
    }

    /**
     * 
     * @return type
     */
    public function isLocked() {

        $lock = $this->fileReadDecode($this->filePath);
        return $lock[$this->ip]['locked'];
    }

    /**
     * 
     * @param type $data
     * @param type $decode
     * @param type $count
     * @return int
     */
    private function setLock($data, $decode, $count) {

        $condition_1 = $this->timeDiff($data[$this->ip]['time']) <= 2;
        $condition_2 = $data[$this->ip]['locked'] == true;
        $condition_3 = $data[$this->ip]['locked'] == false;
        $condition_4 = $this->timeDiff($data[$this->ip]['time']) > 2;
        $condition_5 = $this->unlockTime($data[$this->ip]['time']);

        //if time request is less the 2 second and count is greatn than 3
        if ($condition_1) {
            $decode[$this->ip]['count'] = $data[$this->ip]['count'] + 1;
        }
        //is count it greater than 3
        if ($count > 3 && $condition_1) {
            $decode[$this->ip]['locked'] = true;
        }
        //if already locked || the diff is greater than 2 || lock time is less than 10 minutes
        if ($condition_2 && $condition_4 && $condition_5 == false) { //locked 
            $decode[$this->ip]['locked'] = true;
        }
        //not locked || request is greater than 2 secs
        if ($condition_3 && $condition_4) { // locked false
            $decode[$this->ip]['time'] = $this->time();
        }
        //lock time is over 10 minutes
        if ($condition_5) {
            $decode[$this->ip]['time'] = $this->time();
            $decode[$this->ip]['count'] = 0;
        }

        return $decode;
    }

    /**
     * 
     * @param type $boolean
     */
    public function unLock($boolean = false) {

        if ($boolean) {
            $decode[$this->ip] = array(
                'count' => 0,
                'locked' => false,
                'url' => $this->request->getUri(),
                'time' => $this->time()
            );
            $this->file->writeContent($this->filePath, json_encode($decode), "w");
        }
    }

}
