<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Application
 *
 * @author kamilhurajt
 */

namespace Storyous\Helper;

class Application extends \Storyous\Helper {
    /**
     * @var \Phalcon\Config
     */
    protected $config;
    
    /**
     * @var \Phalcon\Mvc\Application
     */
    protected $application;
    
    /**
     * @param \Phalcon\Mvc\Application $application created instance of application
     * @param \Phalcon\Config $config Configuration
     */
    public function __construct(\Phalcon\Mvc\Application $application, \Phalcon\Config $config) {
        $this->config = $config;
        $this->application = $application;
    }
    /**
     * Register application modules
     */
    public function registerModules(){
        try {
            $modules = array();
            foreach($this->config as $moduleConfig){    
                
                $modules[$moduleConfig->moduleName] =
                         array(
                           'className'     => $moduleConfig->className,
                           'path'          => $moduleConfig->path
                         );
            }
            //add modules to application
            $this->application->registerModules($modules);
        }
        catch(Exception $e){
            throw new \Storyous\Helper\Exception($e->getMessage(), $e->getCode());
        }
    }

    
}
