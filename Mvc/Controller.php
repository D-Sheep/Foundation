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
    //private $_assets;

    protected function initialize() {

        $this->getDi()->getEventsManager()->attach('view:beforeRender', $this);
    }

    protected function beforeRender() {
        $this->view->basePath = $this->getDi()->getUrl()->getBasePath();
    }




}