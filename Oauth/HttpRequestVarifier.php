<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 17/07/14
 */

namespace Foundation\Oauth;


use Phalcon\Http\Request;
use Phalcon\Mvc\Url;

class HttpRequestVarifier implements IOauthSignable {

    /** @var Request */
    protected $request;

    /** @var \Nette\Http\Url */
    protected $url;

    /** @var Array */
    protected $encodedParams;

    protected $_rawBody;

    /**
     *
     * @var \Foundation\Oauth\CryptMethodFactory
     */
    protected  $cryptMethodFactory;

    public function __construct( Request $request, CryptMethodFactory $cryptMethodFactory = NULL){
        $this->request=$request;
        $this->getAllParams();
        $this->getRequestBody();
        $this->cryptMethodFactory = $cryptMethodFactory;
    }

    public function getUrl(){
        if ($this->url === null){
            $this->url = $this->request->getDI()->getSuperUrl();
        }
        return $this->url;
    }

    /** @return \Phalcon\Logger\AdapterInterface */
    public function getLogger(){
        return $this->request->getDI()->getLogger();
    }

    public function getSignatureBaseString(){
        $sig 	= array();
        $sig[]	= $this->request->getMethod();
        $sig[]	= $this->getNormalizedUrl();
        $sig[]	= $this->getNormalizedParams();

        //$this->getLogger()->notice($this->request->getMethod());
        //$this->getLogger()->notice($this->getNormalizedUrl());
        //$this->getLogger()->notice($this->getNormalizedParams());

        return implode('&', array_map(array($this, 'oauthurlencode'), $sig));
    }

    /**
     * Return the normalised url for signature checks
     */
    function getNormalizedUrl (){
        $uri = $this->getUrl();
        //$this->getLogger()->notice("my url: ".var_export($uri, true));
        $url =  $uri->getScheme() . '://'
            . $uri->getUser() . (($uri->getPassword() != '') ? ':' : '')
            . $uri->getPassword() . (($uri->getUser() != '') ? '@' : '')
            . $uri->getHost();

        if ($uri->getPort()
            &&	$uri->getPort() != $this->defaultPortForScheme($uri->getScheme())) {
            $url .= ':'.$uri->getPort();
        }

        if (($uri->getPath() != '')) {
            $url .= $uri->getPath();
        }

        return $url;
    }

    /**
     * Return the complete parameter string for the signature check.
     * All parameters are correctly urlencoded and sorted on name and value
     *
     * @return string
     */
    public function getNormalizedParams (){
        /*
        // sort by name, then by value
        // (needed when we start allowing multiple values with the same name)
        $keys   = array_keys($this->param);
        $values = array_values($this->param);
        array_multisort($keys, SORT_ASC, $values, SORT_ASC);
        */
        $params     = $this->encodedParams;
        //$this->logger->notice("params ".var_export($params, true));
        $normalized = array();

        ksort($params);
        foreach ($params as $key => $value){
            // all names and values are already urlencoded, exclude the oauth signature
            if ($key != 'oauth_signature'){
                if (is_array($value)){
                    $value_sort = $value;
                    sort($value_sort);
                    foreach ($value_sort as $v){
                        $normalized[] = $key.'='.$v;
                    }
                }else{
                    $normalized[] = $key.'='.$value;
                }
            }
        }
        return implode('&', $normalized);
    }

    /**
     * Return the default port for a scheme
     *
     * @param String scheme
     * @return int
     */
    protected function defaultPortForScheme ( $scheme ) {
        switch ($scheme){
            case 'http':	return 80;
            case 'https':	return 443;
            default:
                throw new OauthException('Unsupported scheme type, expected http or https, got "'.$scheme.'"');
                break;
        }
    }

    /**
     * Fetch the content type of the current request
     *
     * @return string
     */
    public function getContentType () {
        $content_type = 'application/octet-stream';
        if (!empty($_SERVER) && array_key_exists('CONTENT_TYPE', $_SERVER)) {
            list($content_type) = explode(';', $_SERVER['CONTENT_TYPE']);
        }
        return trim($content_type);
    }

    /**
     * Get a parameter, value is always urlencoded
     *
     * @param string	name
     * @param boolean	urldecode	set to true to decode the value upon return
     * @return string value		false when not found
     */
    function getParam ( $name, $urldecode = false ){
        if (isset($this->encodedParams[$name])) {
            $s = $this->encodedParams[$name];
        } else if (isset($this->encodedParams[$this->oauthurlencode($name)])) {
            $s = $this->encodedParams[$this->oauthurlencode($name)];
        } else {
            $s = false;
        }
        if (!empty($s) && $urldecode) {
            if (is_array($s)) {
                $s = array_map(array($this,'oauthurldecode'), $s);
            } else {
                $s = $this->oauthurldecode($s);
            }
        }
        return $s;
    }

    public function getEnc($s){
        return $this->getParam($s,true);
    }

    public function get($s){
        return $this->getParam($s);
    }

    /**
     * Decode a string according to RFC3986.
     * Also correctly decodes RFC1738 urls.
     *
     * @param string s
     * @return string
     */
    public function oauthurldecode ( $s ) {
        if ($s === false) {
            return $s;
        } else {
            return rawurldecode($s);
        }
    }

    /**
     * Encode a string according to the RFC3986
     *
     * @param String s
     * @return String
     */
    public function oauthurlencode ( $s ){
        if ($s === false){
            return $s;
        } else {
            return str_replace('%7E', '~', rawurlencode($s));
        }
    }

    public function getAllParams() {
        if ($this->encodedParams === null) {
            //$headers = $this->getHeaders();
            $parameters = "";

            $return = array();

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $matches = array();
                $in = $_SERVER['HTTP_AUTHORIZATION'];
            } else if (function_exists('apache_request_headers')){
                $all = apache_request_headers();
                if (isset($all['Authorization'])) {
                    $in = $all['Authorization'];
                }
            }

            if (isset($in)){
                preg_match_all("/([a-z0-9_\-]+)=\"([^\"]+)\"/i", $in, $matches);
                if ((preg_match("/^oauth\s/i", $in) || preg_match("/^x_auth_\s/i", $in)) && isset($matches[0])) {
                    foreach($matches[0] as $key => $match) {
                        //$return[$this->oauthurldecode($matches[1][$key])] = $this->oauthurldecode($matches[2][$key]);
                        $return[$matches[1][$key]] = $matches[2][$key];
                    }
                }
            }
            // If this is a post then also check the posted variables
            if ((strcasecmp($this->request->getMethod(), 'POST') == 0 || strcasecmp($this->request->getMethod(), 'PUT') == 0) && (!isset($return['xoauth_body_signature']) || !$return['xoauth_body_signature'])) {

                // TODO: what to do with 'multipart/form-data'?
                if ($this->getContentType() == 'multipart/form-data'|| strcasecmp($this->request->getMethod(), 'PUT') == 0) {
                    // Get the posted body (when available)
                    $parameters .= $this->getRequestBodyOfMultipart();
                } else if ($this->getContentType() == 'application/x-www-form-urlencoded') {
                    // Get the posted body (when available)
                    $parameters .= str_replace(["+", "*", "%7E"], ["%20", "%2A", "~"], $this->getRequestBody());
                }
            }

            // Now all is complete - parse all parameters
            foreach (array($parameters) as $params) {
                if (strlen($params) > 0) {
                    $params = explode('&', $params);
                    foreach ($params as $p) {
                        @list($name, $value) = explode('=', $p, 2);
                        if (!strlen($name)) {
                            continue;
                        }

                        if (array_key_exists($name, $return)) {
                            if (is_array($return[$name]))
                                $return[$name][] = $value;
                            else
                                $return[$name] = array($return[$name], $value);
                        } else {
                            $return[$name]  = $value;
                        }
                    }
                }
            }

            // Now all is complete - parse all parameters
            /*foreach ($this->request->getQuery() as $params) {
                if (strlen($params) > 0) {
                    $params = explode('&', $params);
                    foreach ($params as $p) {
                        @list($name, $value) = explode('=', $p, 2);
                        if (!strlen($name)) {
                            continue;
                        } else {
                            $value = $this->oauthurlencode($value);
                        }

                        if (array_key_exists($name, $return)) {
                            if (is_array($return[$name]))
                                $return[$name][] = $value;
                            else
                                $return[$name] = array($return[$name], $value);
                        } else {
                            $return[$name]  = $value;
                        }
                    }
                }
            }*/

            $this->encodedParams = $return;
        }
        //$this->logger->notice("params  ".var_export($return, true));
        $this->getLogger()->commit();
        return $this->encodedParams;
    }









    public function getToken(){
        return $this->request->get('oauth_token');
    }

    public function getSignature(){
        return $this->request->get('oauth_signature');
    }

    public function getConsumerKey(){
        return $this->request->get('oauth_consumer_key');
    }

    public function getSignatureMethod(){
        return $this->request->get('oauth_signature_method');
    }

    public function isSigned(){
        if ($this->getParam('oauth_signature')){
            return true;
        }

        $hs = $this->request->getHeaders();
        if (isset($hs['Authorization']) && strpos($hs['Authorization'], 'oauth_signature') !== false) {
            $signed = true;
        } else {
            $signed = false;
        }

        return $signed;
    }

    protected function encodeUrlParam($key, $value) {
        if (is_array($value)) {
            $j = "";
            foreach ($value as $k => $v) {
                $kArray = is_array($key) ? \array_merge(array(), $key) : array($key);
                $kArray[] = $k;
                $j .= $this->encodeUrlParam($kArray, $v);
            }
            return $j;
        } else {
            $kString = is_array($key) ? array_shift($key) : $key;
            if (is_array($key) && count($key)>0) {
                $kString .= "[".  join("][", $key)."]";
            }
            return  $this->oauthurlencode($kString) . '=' . $this->oauthurlencode($value) . '&';
        }
    }

    /**
     * Verify the signature of the request, using the method in oauth_signature_method.
     * The signature is returned encoded in the form as used in the url.  So the base64 and
     * urlencoding has been done.
     *
     * @param string consumer_secret
     * @param string token_secret
     * @exception OAuthException2 thrown when the signature method is unknown
     * @exception OAuthException2 when not all parts available
     * @exception OAuthException2 when signature does not match
     */
    public function verifySignature ( Secrets $secrets, $token_type = OAuthService::TOKEN_TYPE_ACCESS )
    {
        $required = array(
            'oauth_consumer_key',
            'oauth_signature_method',
            'oauth_timestamp',
            'oauth_nonce',
            'oauth_signature'
        );

        if ($token_type !== false){
            $required[] = 'oauth_token';
        }

        foreach ($required as $req){
            if (!isset($this->encodedParams[$req])){
                throw new OauthException('Can\'t verify request signature, missing parameter "'.$req.'"');
            }
        }

        $this->checkOAuthVersion();

        $this->verifyDataSignature($secrets, $this->getParam('oauth_signature_method'), $this->getParam('oauth_signature'));
    }



    /**
     * Perform some sanity checks.
     *
     * @exception OAuthException2 thrown when sanity checks failed
     */
    function checkOAuthVersion () {
        if ($this->getEnc('oauth_version') === null ) {
            $version = $this->oauthurldecode($this->getEnc('oauth_version'));
            if ($version != '1.0' && $version != '1.0a')
            {
                throw new OAuthException('Expected OAuth version 1.0, got "'.$this->getEnc('oauth_version').'"');
            }
        }
    }

    /**
     * Verify the signature of a string.
     *
     * @param string 	data
     * @param string	consumer_secret
     * @param string	token_secret
     * @param string 	signature_method
     * @param string 	signature
     * @exception OauthException thrown when the signature method is unknown
     * @exception OauthException when signature does not match
     */
    public function verifyDataSignature ( Secrets $secrets, $signature_method, $signature ){

        $sig = $this->cryptMethodFactory->getSignatureMethod($signature_method);
        if (!$sig->verify($this, $secrets, $signature)) {
            throw new OauthException('Signature verification failed ('.$signature_method.')');
        }
    }

    /**
     * Get the body of a POST or PUT.
     *
     * Used for fetching the post parameters and to calculate the body signature.
     *
     * @return string		null when no body present (or wrong content type for body)
     */
    public function getRequestBody (){
        if ($this->_rawBody === null) {
            $body = null;
            if ($this->getContentType() == 'multipart/form-data'){
                $body = \function_exists("http_get_request_body") ? http_get_request_body() : @$_POST[0];
            } else if ($this->request->getMethod() == 'POST' || $this->request->getMethod() == 'PUT') {
                $body = '';
                $fh   = @fopen('php://input', 'r');
                if ($fh) {
                    while (!feof($fh)) {
                        $s = fread($fh, 1024);
                        if (is_string($s)) {
                            $body .= $s;
                        }
                    }
                    fclose($fh);
                }
            }
            $this->_rawBody = $body;
        }
        return $this->_rawBody;
    }


}