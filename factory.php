<?php

/**
 *  simple factory
 * */


class food {

}

class foodFactory {
    public static function factory() {
        return new food;
    }
}

/**
 *  abstract factory
 * */

abstract class AbstractFactory {
    public abstract function create();
} 
class PlanFactory extends AbstractFactory {
    public function create() {
        echo "we are creating plan\n";
    }
}
