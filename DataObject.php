<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 01/08/14
 */

namespace Foundation;


/**
 * Description of DataObject
 *
 * @key _default_object_key
 * @author David Menger
 */
class DataObject implements \ArrayAccess, \Serializable  {

    protected $_data = array();
    protected static $r_cache = array();
    protected static $k_cache = array();


    function __construct($id = null) {
        if ($id !== null) $this->setId($id);
    }

    protected static $annotationCache = array();

    /**
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data) {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
        return $this;
    }

    public function getData() {
        $data = $this->_data;
        foreach ($this->getReflection()->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflection) {
            if ($reflection->isPublic() && !$reflection->isStatic()) {
                $data[$reflection->getName()] = $this[$reflection->getName()];
            }
        }
        return $data;
    }

    public function getId() {
        return $this->{$this->getKeyKey()};
    }

    /**
     * @return mixed
     */
    protected function getKeyKey() {
        $n = get_called_class();
        if (!isset(self::$k_cache[$n])) {
            $key = null;
            $r = $this->getReflection();
            //Parse the annotations in a class
            $reader = new \Phalcon\Annotations\Reader();
            $parsing = $reader->parse($r->getName());
            //Create the reflection
            $annotations = new \Phalcon\Annotations\Reflection($parsing);
            if ($annotations->getClassAnnotations()->has('key')) {
                $key = $annotations->getClassAnnotations()->get('key');
            } else {
                foreach ($r->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    if (!$property->isPublic() || $property->isStatic())    continue;
                    $f = new Field($r, $property);
                    if ($f->isKey()) {
                        $key = $property->getName();
                        break;
                    }
                }
            }
            self::$k_cache[$n] = $key;
        }
        return self::$k_cache[$n];
    }

    public function setId($id) {
        $this[$this->getKeyKey()] = $id;
    }

    public function offsetExists($offset) {
        return ($this->getReflection()->hasProperty($offset) || isset($this->_data[$offset]));
    }

    public function offsetGet($offset) {
        $r = $this->getReflection();
        if ($r->hasProperty($offset) && $r->getProperty($offset)->isPublic()) {
            return $this->$offset;
        } else {
            return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
        }
    }

    public function offsetSet($offset, $value) {
        $r = $this->getReflection();
        if ($r->hasProperty($offset) && $r->getProperty($offset)->isPublic()) {
            $this->$offset = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        $r = $this->getReflection();
        if ($r->hasProperty($offset) && $r->getProperty($offset)->isPublic() ) {
            unset ($this->$offset);
        } else {
            unset($this->_data[$offset]);
        }
    }

    /* TODO je potřeba?
    public function __call($name, $args) {
        parent::__call($name, $args);
    }*/

    public function serialize() {
        return serialize($this->getDataForSerialization(false));
    }

    public function unserialize($serialized) {
        foreach (unserialize($serialized) as $key => $value) {
            $this[$key] = preg_match("/[1-2][0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $value) ? new \DateTime($value) : $value;
        }
    }

    public function getDataForSerialization()
    {
        $data = [];
        foreach ($this->getData() as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format("Y-m-d H:i:s");
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     *
     * @param type $attr
     * @return Field
     */
    public function getFieldReflection($fieldName) {
        foreach (self::getFields() as $key => $value) {
            if ($fieldName == $key) {
                return $value;
            }
        }
        throw new \Foundation\Exception("Field not found");
    }

    /**
     *
     * @return Field <array>
     */
    public static function getFields($justKeys = false) {
        $ref = static::getReflection();
        return self::getFieldsWithReflection($ref, $justKeys);
    }

    public static function getFieldsWithReflection(\ReflectionClass $ref, $justKeys = false) {
        if (!isset (self::$annotationCache[$ref->getName()])) {
            foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic()) {
                    self::$annotationCache[$ref->getName()][$property->getName()] = new Field($ref, $property);
                }
            }
        }
        if ($justKeys) {
            if (!isset(self::$annotationCache[$ref->getName()])) {
                return array();
            }
            $ret = array();
            foreach (self::$annotationCache[$ref->getName()] as $name => $annotation) {
                if ($annotation->isKey()) {
                    $ret[$name] = $annotation;
                }
            }
            return $ret;
        } else {
            return isset (self::$annotationCache[$ref->getName()]) ? self::$annotationCache[$ref->getName()] : array();
        }
    }

    /**
     *
     * @param \ReflectionClass $withClassReflection
     * @return string
     */
    public static function getTypeHash(\ReflectionClass $withClassReflection = null) {
        $ref = $withClassReflection ? $withClassReflection : static::getReflection();
        $props = $ref->getProperties();

        //Parse the annotations in a class
        $reader = new \Phalcon\Annotations\Reader();
        $parsing = $reader->parse($ref->getName());
        //Create the reflection
        $annotations = new \Phalcon\Annotations\Reflection($parsing);

        $text = ($annotations->getClassAnnotations()->has('cached')) ?"X":"0";
        foreach ($props as $prop) {
            $text .= $prop->getName();//.($prop->hasAnnotation('lazy')?"M":"0");
        }
        //\Nette\Diagnostics\Debugger::barDump(array('hash:'=>$text), "HFOR: ".$ref->getName());
        return md5($text);
    }

    /**
     * Access to reflection.
     * @return \ReflectionClass
     */
    public static function getReflection(){
        $n = get_called_class();
        if (!isset(self::$r_cache[$n])) {
            self::$r_cache[$n] = new \ReflectionClass($n);
        }
        return self::$r_cache[$n];
    }

    /**
     *
     * @param type $attr
     * @param type $value
     * @return $this
     */
    public function setAttr($attr, $value) {
        $this[$attr] = $value;
        return $this;
    }

    public function getAttr($attr) {
        return $this[$attr];
    }

    public function release() {
        if (isset($this->_data)) {
            foreach ($this->_data as $key => $value) {
                unset($this[$key]);
            }
            unset($this->_data);
        }
        foreach (get_object_vars($this) as $key => $value) {
            unset($this->$key);
        }
        if (isset($this->_dataIterator)) unset($this->_dataIterator);
        return $this;
    }

    /**
     *
     * @return \Phalcon\DiInterface
     */
    protected static function getContext() {
        return \Phalcon\DI::getDefault();
    }


}