<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Mvc;


use Foundation\Oauth\CryptMethodFactory;
use Foundation\Oauth\HttpRequestVarifier;
use Foundation\Oauth\OAuthService;
use Foundation\Oauth\Secrets;
use Foundation\Security\Authenticator;
use Foundation\Security\Authoriser;
use Nette\Security\User;
use Phalcon\Http\Request;
use Phalcon\Mvc\Controller;
use Storyous\Entities\Account;
use Storyous\Oauth\OauthStore;
use Storyous\Entities\Person;

class ApiController extends Controller {

    const ERR_ACCESS_NOT_PERMITED = 401;
    const ERR_NOT_FOUND = 404;
    const ERR_BAD_INPUT = 402;

    private $isSigned;

    public function initialize(){
        $di = $this->getDI();
        $di->set('oAuthService', function() use($di){
            return new OAuthService($di->get('session'), $this->getHttpRequest(), $this->response,
                $di->getOauthStore());
        }, true);
    }

    /**
     * @var User
     */
    private $_user;

    private $_person;

    private $secrets;

    private $_request;

    public $payLoad;

    /** @return User */
    public function getUser($forceSessionUser = FALSE){
        if ($forceSessionUser){
            $this->di->get('user');
        }
        if ($this->_user === null){
            if ($this->isSigned()){
                try {
                    $this->secrets = $this->di->get('oAuthService')->verifyExtended();
                    $this->_oauthSecrets = $this->secrets;
                    $account = Account::findFirst($this->secrets->account_id);
                } catch (\Foundation\Oauth\OauthException $e) {
                    /*if ($this->getContext()->parameters['debugMode']) {
                        $this->payload->oauthError = $e->getMessage();
                    }
                    \Foundation\Utils\Logger::log("oauth-getuser-error", $e->getMessage(), $this->getHttpRequest()->getAllParams());
                    $account = null;*/
                }
                 $this->_user = new \Nette\Security\User(
                     new OauthStore($this->di->get('modelsManger')),
                     new OauthStore($this->di->get('authenticator')),
                     new OauthStore($this->di->get('authoriser'))
                     );
                $this->di->set('user',$this->_user);
            } else {
                $this->_cachedUser = $this->di->get('user');
            }
        }
        return $this->_user;
    }

    /** @return Person */
    public function getLoggedPerson(){
        if ($this->getUser()->getIdentity()) {
            if (!$this->_person) {
                $this->_person = Person::find(array('person.account_id'=>  $this->getUser()->getId()));
            }
            return $this->_cachedPerson;
        } else {
            return null;
        }
    }

    /** @return Secrets */
    public function getSecrets(){
        $this->getUser();
        return $this->secrets;
    }

    public function getHttpRequest(){
        if ($this->_request === null){
            $this->_request = new HttpRequestVarifier($this->di->getRequest(), new CryptMethodFactory($this->di->getOauthStore()));
        }
        return $this->_request;
    }

    /** @return boolean */
    public function isSigned(){
        if ($this->isSigned === null){
            $this->isSigned = $this->getHttpRequest()->isSigned();
            if ($this->isSigned === null) {
                $this->isSigned = "";
                return false;
            }
        } else if ($this->isSigned === ""){
            return false;
        }
        return $this->isSigned;
    }
}