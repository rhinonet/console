<?php

class RedisCluster{
    public $redis;
    private $server_info = array(array("addr"=>"127.0.0.1","port"=>"6379"));

    public function __construct() {
        $this->redis = new Redis;
    }

    public function check($res) {
        $size = count($this->server_info);
        $server = $this->server_info[$res%$size];
        $this->redis->connect($server['addr'], $server['port']);
    }

    public function set($key, $value) {
        $res = $this->mhash($key);
        $this->check($res);
        $this->redis->set($key, $value);
    }

    public function get($key) {
        $res = $this->mhash($key);
        $this->check($res);
        $this->redis->get($key);
    }

    private function mhash($str) {
        $hash = 0;
        $s = md5($str);
        $seed = 5;
        $len  = 32;
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << $seed) + $hash + ord($s{$i});
        }

        return $hash & 0x7FFFFFFF;    
    }
}



$obj = new RedisCluster;
$obj->set("user", "zc");
