<?php

interface Weather {
    public function display();
}

class ProxyWeather implements Weather {
    private $client;
    public function __construct() {
        $this->client = new RealWeather;
    }
    public function display() {
        $this->client->display();
    }
}

class GenericProxyWeather {
    private $client;
    public function __construct($client) {
        $this->client = $client;    
    }
    public function __call($method, $args) {
        call_user_func_array(array($this->client, $method), $args);
    }
}

class RealWeather implements Weather {
    public function display() {
        echo "this is realweather\n"; 
    }
}

class Client {
    public static function main1 () {
        $proxy = new ProxyWeather;
        $proxy->display();
    }
    public static function main2() {
        $proxy = new GenericProxyWeather(new RealWeather);
        $proxy->display();
    }
}

Client::main2();
