<?php

class FileLock {
    public $fp;
    public $lock_file;
    public function lock() {
        $this->lock_file = "/tmp/" . __CLASS__ . ".php";
        if (!file_exists($this->lock_file)) {
            $this->fp = fopen($this->lock_file, "wa");
        } else {
            echo "File Locked\n";exit;
        }
        
    }
    public function unlock() {
        if(is_resource($this->fp)) {
            fclose($this->fp);
        }
        unlink($this->lock_file);
    }
    public function doTest() {
        $this->lock();
        sleep(60);
        $this->unlock();
    }
    
}

$obj = new FileLock;
$obj->doTest();
