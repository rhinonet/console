<?php
namespace yii\di;

use Yii;
use yii\base\InvalidConfigException;


class Instance
{
    
    public $id;


    
    protected function __construct($id)
    {
        $this->id = $id;
    }

    
    public static function of($id)
    {
        return new static($id);
    }

    
    public static function ensure($reference, $type = null, $container = null)
    {
        if ($reference instanceof $type) {
            return $reference;
        } elseif (is_array($reference)) {
            $class = isset($reference['class']) ? $reference['class'] : $type;
            if (!$container instanceof Container) {
                $container = Yii::$container;
            }
            unset($reference['class']);
            return $container->get($class, [], $reference);
        } elseif (empty($reference)) {
            throw new InvalidConfigException('The required component is not specified.');
        }

        if (is_string($reference)) {
            $reference = new static($reference);
        }

        if ($reference instanceof self) {
            $component = $reference->get($container);
            if ($component instanceof $type || $type === null) {
                return $component;
            } else {
                throw new InvalidConfigException('"' . $reference->id . '" refers to a ' . get_class($component) . " component. $type is expected.");
            }
        }

        $valueType = is_object($reference) ? get_class($reference) : gettype($reference);
        throw new InvalidConfigException("Invalid data type: $valueType. $type is expected.");
    }

    
    public function get($container = null)
    {
        if ($container) {
            return $container->get($this->id);
        }
        if (Yii::$app && Yii::$app->has($this->id)) {
            return Yii::$app->get($this->id);
        } else {
            return Yii::$container->get($this->id);
        }
    }
}
