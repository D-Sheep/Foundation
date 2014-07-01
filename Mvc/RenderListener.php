<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 23/06/14
 */

namespace Foundation\Mvc;


use Foundation\Mvc\AssetsManager,
    Phalcon\Assets;

class RenderListener {
    private $di;

    public function __construct($di){
        $this->di = $di;
    }

    public function beforeRender(){
        $this->di->get('assetManager')->initialize($this->di->get('assets'));
    }
}