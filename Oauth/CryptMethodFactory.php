<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 24/07/14
 */

namespace Foundation\Oauth;


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
     * @var \Foundation\Oauth\Store
     */
    protected $store;


    function __construct(Store $store) {
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

        $string = "\\Foundation\\Oauth\\SignatureMethod\\".self::$methods[$signature_method];
        $obj = new $string(); //TODO funguje?
        //$obj = \Nette\Reflection\ClassType::from($string)->newInstanceArgs();
        if ($obj instanceof SignatureMethod\IStoreRequired) {
            $obj->setStore($this->store);
        }
        return $obj;
    }
}