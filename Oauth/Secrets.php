<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Oauth;


use Foundation\Oauth\IOauthConsumer,
    Foundation\Oauth\IOauthToken;

class Secrets {
    /**
     *
     * @var IOauthToken
     */
    protected $serverToken;

    /**
     *
     * @var IOauthConsumer
     */
    protected $serverRegister;

    function __construct(IOauthConsumer $serverRegister = null, IOauthToken $serverToken = null) {
        $this->serverToken = $serverToken;
        $this->serverRegister = $serverRegister;
        if ($serverRegister) {
            $this->consumer_key = $serverRegister->consumer_key;
            $this->consumer_secret = $serverRegister->consumer_secret;
            $this->osr_id = $serverRegister->oauth_server_registry_id;
        }
        if ($serverToken) {
            $this->token_secret = $serverToken->token_secret;
            $this->token = $serverToken->token;
            $this->account_id = $serverToken->account_id;
            $this->ost_id = $serverToken->oauth_server_token_id;
        }
    }

    public static function initWithTokens($consumer_secret, $consumer_key, $token_secret, $token) {
        $ret = new \Foundation\Oauth\Secrets();
        $ret->consumer_secret = $consumer_secret;
        $ret->consumer_key = $consumer_key;
        $ret->token_secret = $token_secret;
        $ret->token = $token;
        return $ret;
    }

    /**
     *
     * @return \Foundation\Oauth\IOauthServerToken
     */
    public function getServerToken() {
        return $this->serverToken;
    }

    /**
     *
     * @return \Foundation\Oauth\IOauthServerRegistry
     */
    public function getServerRegister() {
        return $this->serverRegister;
    }


    public function getIdentity(){

        return $this->getServerToken()->Acount;//TODO opravdu?!
    }

    /**
     *
     * @var string
     */
    public $consumer_secret;

    /**
     *
     * @var string
     */
    public $consumer_key;


    /**
     *
     * @var string
     */
    public $token_secret;

    /**
     *
     * @var string
     */
    public $token;

    /**
     *
     * @var int
     */
    public $osr_id;

    /**
     *
     * @var int
     */
    public $ost_id;

    /**
     *
     * @var int
     */
    public $ttl;
}