<?php
/**
 * Created by JetBrains PhpStorm.
 * User: davidmenger
 * Date: 24/05/14
 * Time: 17:32
 * To change this template use File | Settings | File Templates.
 */

namespace Foundation\Mvc;


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
        $lang = $this->getDi()->getLang()->getUserDefaultLanguage();
        $this->view->lang = $lang;
        $this->view->basePath = $this->getDi()->getUrl()->getBasePath();
        $this->view->basePathWithLang = $this->getDi()->getUrl()->getBasePath().$lang."/";
        $this->view->hostUrl = $this->getDi()->getSuperUrl()->getHostUrl();
        $this->view->baseUrl = $this->getDi()->getSuperUrl()->getBaseUrl();
        $this->view->baseUrlWithLang = $this->getDi()->getSuperUrl()->getBaseUrl().$lang."/";
    }




}