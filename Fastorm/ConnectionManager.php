<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 13/08/14
 * Time: 11:07
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Fastorm;


use Phalcon\DiInterface;

class ConnectionManager extends \Fastorm\ConnectionManager {

    protected $di;

    /**
     * @param $defaultConnectionName
     * @param DiInterface $di
     */
    public function __construct($defaultConnectionName, DiInterface $di)
    {
        parent::__construct($defaultConnectionName);
        $this->di = $di;
    }


    /**
     * @param $name
     * @return object
     */
    public function createConnection($name)
    {
        return $this->di->getShared($name);
    }


}