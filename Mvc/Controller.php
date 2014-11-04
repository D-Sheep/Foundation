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
use Foundation\Security\Authenticator;
use Foundation\Security\Authoriser;
use Foundation\Security\MemoryStorage;
use Foundation\Security\SessionStorage;
use Nette\Security\Security\User;
use Phalcon\Http\Request;
use Storyous\Core\Entities\Person;


/**
 * Class Controller
 * @package Foundation\Mvc
 *
 * @method \Foundation\DI\Factory getDi() getDi()
 * @property \Foundation\DI\Factory $di
 *
 */
class Controller extends \Phalcon\Mvc\Controller {

    protected function initialize() {

        $this->getDi()->getEventsManager()->attach('view:beforeRender', $this);
    }

    protected function beforeRender() {
        $this->view->basePath = $this->getDi()->getUrl()->getBasePath();
    }


}