<?php

class DoubleQueue {
    private $left_pointer = 128;
    private $right_pointer = 128;
    private $queue = array();

    public function lpush($val) {
        if ($this->left_pointer < 128) {
            $this->right_pointer = 127;
        }
        $this->queue[--$this->left_pointer] = $val;
    }

    public function rpush($val) {
        if ($this->right_pointer > 128) {
            $this->left_pointer = 129;
        }
        $this->queue[++$this->right_pointer] = $val;
    }

    public function lpop() {
        unset($this->queue[$this->left_pointer++]);
    }

    public function rpop() {
        unset($this->queue[$this->right_pointer--]);
    }

    public function get_length() {
        return count($this->queue);
    }

    public function get_queue() {
        $str = "";
        ksort($this->queue);
        foreach($this->queue as $v) {
            $str .= $v . " -> ";
        }
        return substr($str, 0, strrpos($str, "->"));
    }
}


$obj = new DoubleQueue;
$obj->rpush(1);
$obj->rpush(2);
$obj->lpush(3);
$obj->rpop();
$obj->lpop();
$obj->lpop();
echo $obj->get_length() . "\n";
echo $obj->get_queue() . "\n";
