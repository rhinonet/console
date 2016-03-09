<?php

interface Ring {
    public function ring_out($machine);
}
class Ring_up implements Ring {
    public function ring_out($machine) {
        if (count($machine) > 0)  {
            foreach ($machine as $m) {
                if(count($m) > 0) {
                    foreach ($m as $v) {
                        $v->reaction(1);
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
            foreach ($machine as $m) {
                if(count($m) > 0) {
                    foreach ($m as $v) {
                        $v->reaction(0);
                    }
                }
            }
        }
        return;       
    }

}
class School_Ring {
    public $name = "school_ring";
    public $ring_state = false;
    public function ring_out($machine) {
        $this->ring_state = !$this->ring_state;
        if ($this->ring_state) {
            $obj = new Ring_up; 
        } else {
            $obj = new Ring_down;
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
        if ($state) {
            $this->begin_class();
            return;
        }
        $this->end_class();
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
        if ($state) {
            $this->listen();
            return;
        }
        $this->end_listen();
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
        $this->machine[$be_watch->name][] = $watch;
    }
}
$zhuce = new Zhuce;
$s_ring = new School_Ring();
$teacher = new Teacher("zhangsan");
$student = new Student("lisi");
$zhuce->do_zhuce($s_ring, $teacher);
$zhuce->do_zhuce($s_ring, $student);
$s_ring->ring_out($zhuce->machine);
$s_ring->ring_out($zhuce->machine);
