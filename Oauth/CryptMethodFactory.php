<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 24/07/14
 */

namespace Foundation\Oauth;


use Foundation\Oauth\SignatureMethod\IStoreRequired;
use Storyous\Oauth\OauthStore;

class CryptMethodFactory {
    const HMAC_SHA1 = 'HMAC-SHA1';
    const MD5 = 'MD5';
    const PLAINTEXT = 'PLAINTEXT';
    const RSA_SHA1 = 'RSA-SHA1';

    public static $methods = array(
        self::HMAC_SHA1 => "HMAC_SHA1",
        self::MD5 => "MD5",
        self::PLAINTEXT => "PLAINTEXT",
        self::RSA_SHA1 => "RSA_SHA1"
    );

    /**
     *
     * @var OauthStore
     */
    protected $store;


    function __construct(OauthStore $store) {
        $this->store = $store;
    }

    /**
     *
     * @param string $signature_method
     * @return \Foundation\Oauth\IOauthStore|\Foundation\Oauth\IOAuthSignable
     * @throws OauthException
     */
    public function getSignatureMethod($signature_method) {
        if (!isset(self::$methods[$signature_method])) {
            throw new OauthException("Signature method ".$signature_method." is not supported");
        }

        $path= APP_DIR."/library/Foundation/Oauth/SignatureMethod/".self::$methods[$signature_method].".php";

        require $path;

        $string = "\\Foundation\\Oauth\\SignatureMethod\\".self::$methods[$signature_method];

        $obj = new $string();
        if ($obj instanceof IStoreRequired) {
            $obj->setStore($this->store);
        }
        return $obj;
    }
}