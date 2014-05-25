<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 17:32
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;


use Phalcon\Assets\Manager;

/**
 * Class Controller
 * @package Foundation\Mvc
 *
 * @method \Foundation\DI\Factory getDi() getDi()
 * @property \Foundation\DI\Factory $di
 *
 */
class Controller extends \Phalcon\Mvc\Controller {



    /**
     * @var AssetsManager
     */
    private $_assets;

    protected function initialize() {

        $this->getAssets()->initialize($this);
        // {{ assets.outputCss('header') }}
    }

    /**
     * @return AssetsManager
     */
    public function getAssets()
    {
        if ($this->_assets === null) {
            $this->_assets = new AssetsManager($this->getDi()->getAssets());
        }
        return $this->_assets;
    }




}