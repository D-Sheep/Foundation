<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 01/08/14
 */

namespace Foundation;


class Field {

    /** @var \ReflectionClass */
    private $refClass;

    /** @var \ReflectionProperty */
    private $property;

    /** @var boolean */
    private $isKey;

    public function __constructor(\ReflectionClass $ref, \ReflectionProperty $property){
        $this->refClass = $ref;
        $this->property = $property;
    }

    /**
     * Has the class in annotation this property as key?
     *
     * @return boolean
     */
    public function isKey(){
        if ($this->isKey === null){
            foreach ($this->getAnnotationClass()->getAll('key') as $key){
                if ($key == $this->property->getName()){
                    return true;
                }
            }
        }
        return $this->isKey;
    }

    /**
     * @return \Phalcon\Annotations\Collection
     */
    private function getAnnotationClass(){
        $reader = new \Phalcon\Annotations\Reader();
        $parsing = $reader->parse($this->refClass->getName());
        //Create the reflection
        $annotations = new \Phalcon\Annotations\Reflection($parsing);
        return $annotations->getClassAnnotations();

    }
}