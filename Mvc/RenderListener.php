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

    /** @var AssetsManager */
    private $assetManager;

    /** @var Assets */
    private $assets;

    public function __construct($assetManger, $assets){
        $this->assetManager = $assetManger;
        $this->assets = $assets;
    }

    public function beforeRender(){
        $this->assetManager->initialize($this->assets);
    }
}