<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Db
 *
 * @author kamilhurajt
 */
use Storyous\Db\Adapter\Pdo;

namespace Storyous;

class Db {
    /**
     * @param String $adapter adapter name ( Mysql )
     * @return \Storyout\Db\Adapter\Pdo PDO connection
     */
    public static function factory($adapter,array $config){
        if(class_exists($adapter)){
            return new $adapter($config);
        }
    }
    
}
