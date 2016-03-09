<?php

interface Ring {
    public function ring_out($machine);
}
class Ring_up implements Ring {
    public function ring_out($machine) {
        if (count($machine) > 0)  {
            foreach ($machine as $k => $m) {
                if ($k == "ring") { 
                    if(count($m) > 0) {
                        foreach ($m as $v) {
                            $v->reaction(1);
                        }
                    }
                } elseif ($k == "teacher") {
                    if (count($m) > 0) {
                        foreach ($m as $v) {
                            $v->reaction(2);
                        }
                    }
                } elseif ($k == "student") {
                    if (count($m) > 0) {
                        foreach($m as $v) {
                            $v->reaction(2);
                        }
                    }
                }
            }
        }
        return;       
    }
}
class Ring_down implements Ring {
    public function ring_out($machine) {
        if (count($machine) > 0)  {
            foreach ($machine as $k => $m) {
                if ($k == "ring") { 
                    if(count($m) > 0) {
                        foreach ($m as $v) {
                            $v->reaction(0);
                        }
                    }
                } elseif ($k == "teacher") {
                    if (count($m) > 0) {
                        foreach ($m as $v) {
                            $v->reaction(0);
                        }
                    }
                } elseif ($k == "student") {
                    if (count($m) > 0) {
                        foreach($m as $v) {
                            $v->reaction(0);
                        }
                    }
                }
            }
        }
        return;       
    }
}
class School_Ring {
    public $shenfen = "ring";
    public $name = "school_ring";
    public $ring_state = false;
    public function ring_out($machine) {
        $this->ring_state = !$this->ring_state;
        if ($this->ring_state) {
            $obj = new Ring_up; 
        } else {
            $obj = new Ring_down;
            unset($machine["student"]);
            unset($machine["teacher"]);
        }
        $obj->ring_out($machine);
    }
}
class Teacher {
    public $shenfen = "teacher";
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
    public function reaction($state) {
        if ($state == 1) {
            $this->listen_ring();
            return;
        } elseif($state == 2) {
            $this->begin_class();
            return;
        }
        $this->listen_ring();
        $this->end_class();
    }
    public function listen_ring() {
        echo $this->name . " listen ring\n";
    }
    public function begin_class() {
        echo $this->name . " begin_class\n";
    }
    public function end_class() {
        echo $this->name . " end_class\n";
    }
}
class Student {
    public $shenfen = "student";
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
    public function reaction($state) {
        if ($state == 1) {
            $this->listen_ring();
            $this->ready_listen();
            return;
        } elseif($state == 2) {
            $this->listen();
            return;
        }             
        $this->listen_ring();
        $this->end_listen();
    }
    public function listen_ring() {
        echo $this->name . " listen ring\n";
    }
    public function ready_listen() {
        echo $this->name . " ready listen\n";
    }
    public function listen() {
        echo $this->name . " listen class\n";
    }
    public function end_listen() {
        echo $this->name . " end listen class\n";
    }
}
class Zhuce {
    public $machine;
    public function do_zhuce($be_watch, $watch) {
        $this->machine[$be_watch->shenfen][] = $watch;
    }
}
$zhuce = new Zhuce;
$s_ring = new School_Ring();
$teacher = new Teacher("zhangsan");
$student = new Student("lisi");
$zhuce->do_zhuce($s_ring, $teacher);
$zhuce->do_zhuce($s_ring, $student);
$zhuce->do_zhuce($student, $teacher);
$zhuce->do_zhuce($teacher, $student);
$s_ring->ring_out($zhuce->machine);
$s_ring->ring_out($zhuce->machine);
$s_ring->ring_out($zhuce->machine);
