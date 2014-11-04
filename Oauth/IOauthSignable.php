<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Oauth;


interface IOauthSignable {
    public function getSignatureBaseString();
    public function isSigned();
    public function oauthurldecode ( $s );
    public function oauthurlencode ( $s );
    public function getParam($s, $urldecode = false );
    public function getEnc($s);
    public function get($s);
    public function verifySignature ( Secrets $secrets, $token_type = OAuthService::TOKEN_TYPE_ACCESS );
    public function getContentType ();
    public function verifyDataSignature ( Secrets $secrets, $signature_method, $signature );
    public function getRequestBody ();
}