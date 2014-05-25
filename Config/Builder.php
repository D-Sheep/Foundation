<?php



namespace Foundation\Config;

class Builder {

    const ADAPTER_JSON = 'json';
    const ADAPTER_PHP = 'php';
    const ADAPTER_INI = 'ini';

    /**
     * @param String $adapter Adapter name ( ini, json, php )
     * @param String $configFilePath Source path to config file
     * @return \Phalcon\Config returns filled intance of config object
     */
    public static function factory($adapter,$configFilePath){

        $adapterName = "\\Phalcon\\Config\\Adapter\\".ucfirst($adapter);

        if (class_exists($adapterName)){
            return new $adapterName($configFilePath);
        } else {
            throw new ConfigException("Adapter ".$adapter." doesn't exists.");
        }
    }
}
