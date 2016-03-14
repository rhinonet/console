<?php

class MemCluster{

    public $mem;
    private $servers = array(
        array("host" => "127.0.0.1", "port" => "11211", "weight" => "50"),
        array("host" => "127.0.0.1", "port" => "11222", "weight" => "50"),
    );
    
    public function __construct() {
        $this->mem = new memcache;
        $this->init();
    }

    private function init() {
        if (is_array($this->servers) && count($this->servers)) {
            foreach ($this->servers as $server) {
                $this->mem->addServer($server['host'], $server['port'], true, $server['weight']);
            }
        } else {
            echo "error line " . __LINE__;exit;
        }
    }
 
    public function mset($key, $value, $flag = 0, $expire = 30) {
        $this->mem->set($key, $value, $flag, $expire); 
    }


    public function mget($key, $flag = 0) {
       return  $this->mem->get($key, $flag);
    }

    public function mdelete($key, $timeout = 0) {
        return $this->mem->delete($key, $timeout);
    }

    public function mflush() {
        $this->mem->flush();    
    }

    public function mgetstatu() {
        return $this->mem->getStats() || false;
    }

    public function mreplace($key, $value) {
        $this->mem->replace($key, $value);
    }


}

$mem = new MemCluster;
$data = array(
            array("a1", "123"),
            array("a2", "234"),
            array("a3", "345"),
            array("a4", "456"),
            array("a5", "567"),
            array("a6", "678"),
            array("a7", "789"),
            array("a8", "890"),
        );
foreach ($data as $v) {
    $mem->mset($v[0], $v[1], 0, 3600);
}
for($a = 3; $a < 6;$a++) {
    echo $mem->mget("a$a")."\n";
}
echo "over";exit;
