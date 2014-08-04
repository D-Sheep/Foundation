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
        $session = static::getContext()->get('session')->get('processcontext');

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
        $session = static::getContext()->get('session')->get('processcontext');
        $session->setExpiration('+ 2 hours');
        $session[$obj->id] = $obj;
        return $obj;
    }

    public function go(\Phalcon\Mvc\Controller $presenter = null) {
        $presenter = $presenter ? $presenter : \Nette\Environment::getApplication()->getPresenter();
        if ($this->callback) {
            $url = $this->callback;
            $this->callback = null;

            if ($presenter->hasFlashSession()) {
                $sign = strpos($url, "?")===false?"?":"&";
                $url .= $sign . \Nette\Application\UI\Presenter::FLASH_KEY . "=" .urlencode($presenter->getParameter(\Nette\Application\UI\Presenter::FLASH_KEY));
            }

            $presenter->redirectUrl($url);
        }
    }

    public function goLoginSignUp(\Nette\Application\UI\Presenter $presenter, $goBackToCurrentPage = false) {
        if ($goBackToCurrentPage) {
            $this->setCallback($presenter->link('this', array('invalidate'=>true)));
        }
        $presenter->redirect(':Front:SignUp:default', array('id'=>  $this->id));
    }


}