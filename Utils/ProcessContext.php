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
use Phalcon\Http\Response;

class ProcessContext extends \Fastorm\DataObject {

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
     * @return \Phalcon\DiInterface
     */
    protected static function getDi() {
        return \Phalcon\DI::getDefault();
    }


    /**
     *
     * @return ProcessContext
     */
    public static function getById($id, $badRequestIfNotFound = false, array $validateParams = null) {

        $session = static::getDi()->getSession();
        $sessionname = "processcontext_".$id;
        if ($session->has($sessionname)){
            if ($validateParams && $validateParams) {
                foreach ($validateParams as $param) {
                    if (empty($session[$id][$param])) {
                        throw new BadRequestException();
                    }
                }
            }
            return $session->get($sessionname);
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
        $session = static::getDi()->getSession();
        //$session->setExpiration('+ 2 hours');  TODO je problem, že neumi expiration?
        
        $session->set("processcontext_".$obj->id, $obj);
        return $obj;
    }

    public function go() {
        //$presenter = $presenter ? $presenter : \Nette\Environment::getApplication()->getPresenter();
        //$logger->notice("ProcessContext: callback = ".$this->callback);
        if ($this->callback) {
            //$logger->notice("ProcessContext: jsem v ifu");
            $url = $this->callback;
            $this->callback = null;
            //$response = new \Phalcon\Http\Response();

            //$logger->notice("ProcessContext: url = ".$url);

            /* kdyby se nepředávala flash zpráva od minula
             * if ($presenter->hasFlashSession()) {
                $sign = strpos($url, "?")===false?"?":"&";
                $url .= $sign . \Nette\Application\UI\Presenter::FLASH_KEY . "=" .urlencode($presenter->getParameter(\Nette\Application\UI\Presenter::FLASH_KEY));
            }*/
            //interni redirect
            //$response->redirect($url);
            return $url;
        }
    }

    public function goLoginSignUp($goBackToCurrentPage = false) {
        $url = static::getDi()->getUrl();
        if ($goBackToCurrentPage) {
            $this->setCallback($url->get(array('for' => 'this', 'sal'=>$this->id)));
        }
        //$r = new Response();
        //$r->redirect("api/login/".$this->id);
        return $this->id;
        //return $r;
        //$presenter->redirect(':Front:SignUp:default', array('id'=>  $this->id));
    }


}