<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Mvc;


use Foundation\Logger;
use Foundation\Oauth\CryptMethodFactory;
use Foundation\Oauth\HttpRequestVerifier;
use Foundation\Oauth\OAuthService;
use Foundation\Oauth\Secrets;
use Foundation\Security\Authenticator;
use Foundation\Security\Authoriser;
use Foundation\Security\MemoryStorage;
use Foundation\Security\SessionStorage;
use Nette\Security\Security\User;
use Phalcon\Http\Request;
use Phalcon\Mvc\Controller;
use Storyous\Core\Entities\Account;
use Storyous\Core\Lang\LazyTString;
use Storyous\Oauth\OauthStore;
use Storyous\Core\Entities\Person;
use Storyous\Security\AuthenticatorStorage;
use Storyous\Security\AuthoriserStorage;

class ApiController extends Controller {

    const ERR_ACCESS_NOT_PERMITED = 401;
    const ERR_BAD_REQUEST = 400;
    const ERR_NOT_FOUND = 404;
    const ERR_BAD_INPUT = 402;
    const ERR_FAILED = 401;
    const ERR_NOT_AUTHORIZED = 403;

    private $isSigned;

    /**
     * @var User
     */
    private $_user;

    private $_person;

    private $secrets;

    public $payload;

    public function initialize(){
        $di = $this->getDI();
        $di->set('httpRequest', function () use($di){
            return new \Foundation\Oauth\HttpRequestVerifier($di->getRequest(),
                new CryptMethodFactory($di->getOauthStore()));
        },true);
        $di->set('oAuthService', function() use($di){
            return new OAuthService($di->get('session'), $di->getHttpRequest(), $this->response,
                $di->getOauthStore());
        }, true);
        $this->payload = (object) [];
    }

    /**
     *
     * @return User */
    public function getUser($forceSessionUser = FALSE){
        if ($forceSessionUser){
            return $this->di->getUser();
        }

        if ($this->_user === null){
            if ($this->isSigned()){
                try {
                    $this->secrets = $this->di->getOAuthService()->verifyExtended();
                    $this->_oauthSecrets = $this->secrets;
                    $person = $this->getDI()->getPersons()->getPersonByAcountId($this->secrets->account_id);
                } catch (\Foundation\Oauth\OauthException $e) {
                    /*if ($this->getContext()->parameters['debugMode']) {
                        $this->payload->oauthError = $e->getMessage();
                    }
                    \Foundation\Utils\Logger::log("oauth-getuser-error", $e->getMessage(), $this->getHttpRequest()->getAllParams());
                    $account = null;*/
                }
                $this->_user = new User(
                     new MemoryStorage($person),
                     $this->di->getAuthenticator(),
                     $this->di->getAuthoriser()
                     );
                $this->di->set('user',$this->_user);
            } else {
                $this->_user = $this->getDI()->getUser();
            }
        }
        return $this->_user;
    }

    /** @return Person */
    public function getLoggedPerson(){

        if (!$this->_person) {
            $user = $this->getUser();
            $this->_person = $user->getIdentity();
        }
        return $this->_person;
    }

    /** @return Secrets */
    public function getSecrets(){
        $this->getUser();
        return $this->secrets;
    }

    public function getHttpRequest(){
        return $this->getDI()->getHttpRequest();
    }

    /** @return boolean */
    public function isSigned(){
        return false;
        //@todo pro oauth
        try {
            if ($this->isSigned === null){
                Logger::debug("login", "signed je null");
                $di = $this->getDI();
                $request = $di->getHttpRequest();
                $signed = $request->isSigned();
                Logger::debug("login", "api controler signed je ".var_export($signed, true));
                $this->isSigned = $signed;
            }
            Logger::debug("login", "api controler isSigned je ".var_export($this->isSigned, true));
            return $this->isSigned;
        } catch (\Exception $e){
            throw $e;
        }
    }

    public function sendResponseOk() {
        $this->payload->ok = 1;
        $this->response
            ->setContentType('application/json')
            ->setJsonContent($this->payload)->send();
    }

    public function sendResponseError($code, $message = null) {

        if ($message === null) {
            $message = $this->translateCodeToMessage($code);
        }

        $this->payload->ok = 0;
        $this->payload->error = $message;
        $this->payload->code = $code;
        $this->response
            ->setStatusCode($code, $message)
            ->setContentType('application/json')
            ->setJsonContent($this->payload)->send();
    }

    protected function translateCodeToMessage($code) {
        switch ($code) {
            case self::ERR_NOT_FOUND:
                return "Not found";

            case self::ERR_BAD_INPUT:
                return "Bad input";

            case self::ERR_ACCESS_NOT_PERMITED:
                return "Access not permitted";

            case self::ERR_BAD_REQUEST:
                return "Bad request";

            case self::ERR_FAILED:
                return "Process has failed";

            case self::ERR_NOT_AUTHORIZED:
                return "User has to be logged in.";

            default:
                return "Server error";
        }
    }

    public static function isRobot($userAgent) {
        return preg_match("/(bot|facebook|googlebot|facebookexternalhit|twitterbot|crawler)/i", $userAgent);
    }
}