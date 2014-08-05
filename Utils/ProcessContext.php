<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 04/08/14
 *
 * @key id
 */

namespace Foundation\Utils;

use Foundation\BadRequestException;
use Foundation\DataObject;

class ProcessContext extends DataObject {

    /**
     * @key
     * @var string
     */
    public $id;

    /**
     *
     * @var string
     */
    public $callback;

    /**
     *
     * @var string
     */
    public $registerHeader;

    /**
     *
     * @var string
     */
    public $registerContent;



    /**
     *
     * @return ProcessContext
     */
    public static function getById($id, $badRequestIfNotFound = false, array $validateParams = null) {
        $session = static::getDi()->getSession->get('processcontext');

        if (isset($session[$id])) {
            if ($validateParams && $validateParams) {
                foreach ($validateParams as $param) {
                    if (empty($session[$id][$param])) {
                        throw new BadRequestException();
                    }
                }
            }
            return $session[$id];
        } else if ($badRequestIfNotFound) {
            throw new BadRequestException();
        } else {
            return null;
        }
    }

    /**
     *
     * @param type $callback
     * @return $this
     */
    public function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }

    /**
     *
     * @param type $callback
     * @return $this
     */
    public function setRegisterHeader($registerHeader) {
        $this->registerHeader = $registerHeader;
        return $this;
    }

    /**
     *
     * @param type $callback
     * @return $this
     */
    public function setRegisterContent($registerContent) {
        $this->registerContent = $registerContent;
        return $this;
    }


    /**
     *
     * @return ProcessContext
     */
    public static function create() {
        $obj = new ProcessContext();
        $obj->id = md5(str_repeat(microtime()."microsalt", 2));
        $session = static::getDi()->getSession()->get('processcontext');
        $session->setExpiration('+ 2 hours');
        $session[$obj->id] = $obj;
        return $obj;
    }

    public function go() {
        //$presenter = $presenter ? $presenter : \Nette\Environment::getApplication()->getPresenter();
        if ($this->callback) {
            $url = $this->callback;
            $this->callback = null;
            $response = new \Phalcon\Http\Response();

            /* kdyby se nepředávala flash zpráva od minula
             * if ($presenter->hasFlashSession()) {
                $sign = strpos($url, "?")===false?"?":"&";
                $url .= $sign . \Nette\Application\UI\Presenter::FLASH_KEY . "=" .urlencode($presenter->getParameter(\Nette\Application\UI\Presenter::FLASH_KEY));
            }*/
            //interni redirect
            $response->redirect($url);
            $response->send();
        }
    }

    public function goLoginSignUp($goBackToCurrentPage = false) {
        if ($goBackToCurrentPage) {
            $url = static::getDi()->getUrl();
            $this->setCallback($url->get(array('for' => 'this', 'invalidate'=>true)));
        }
        //TODO !!!
        //$presenter->redirect(':Front:SignUp:default', array('id'=>  $this->id));
    }


}