<?php

class A {
    public function b(){
        echo "it function a\n";
    }
    public function c(){
    }
}
class B {
    public function d() {
        echo "it function c\n";
    }
    public function e() {
    
    }
}


class C {
    public function __construct() {
        $this->a = new A;
        $this->b = new B;
    }
    public function b() {
        $this->a->b();
    }
    public function d() {
        $this->b->d();
    }
}

(new C)->b();
(new C)->d();
