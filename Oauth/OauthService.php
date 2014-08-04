<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kathy
 * Date: 16/07/14
 */

namespace Foundation\Oauth;

use Phalcon\Http\Response,
    Foundation\Oauth\IOauthSignable,
    Foundation\Oauth\IOauthStore,
    Storyous\Account,
    Foundation\Oauth\IOauthConsumer,
    Foundation\Oauth\IOauthToken,
    Phalcon\Session\AdapterInterface;

class OAuthService {

    const TOKEN_TYPE_ACCESS = 'access';
    const TOKEN_TYPE_REQUEST = 'request';

    /** @var IOauthSignable */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var IOauthStore */
    protected $store;

    /** @var AdapterInterface */
    protected $session;

    protected $allowed_uri_schemes = array(
        'http',
        'https'
    );

    protected $disallowed_uri_schemes = array(
        'file',
        'callto',
        'mailto'
    );

    public function getResponse() {
        return $this->response;
    }

    function __construct(AdapterInterface $session, IOauthSignable $request, Response $response, IOauthStore $store) {
        $this->request = $request;
        $this->response = $response;
        $this->store = $store;
        $this->session = $session;
    }

    /**
     * Verify the request if it seemed to be signed.
     *
     * @param String token_type the kind of token needed, defaults to 'access'
     * @exception OAuthException2 thrown when the request did not verify
     * @return boolean	true when signed, false when not signed
     */
    public function verifyRequestIfSigned ( $token_type = self::TOKEN_TYPE_ACCESS ){
        if ($this->request->get('oauth_consumer_key')) {
            //$this->logger->start();
            $this->verifyRequest($token_type);
            $signed = true;
            //$this->logger->flush();
        } else {
            $signed = false;
        }
        return $signed;
    }

    /**
     * Verify the request
     *
     * @param String token_type the kind of token needed, defaults to 'access' (false, 'access', 'request')
     * @exception OAuthException2 thrown when the request did not verify
     * @return int user_id associated with token (false when no user associated)
     */
    public function verifyRequest (IOauthSignable $request, $token_type = self::TOKEN_TYPE_ACCESS ){
        $this->request = $request;
        $retval = $this->verifyExtended($token_type);
        return $retval->account_id;
    }

    public function deauthorizeAccessToken() {
        $token = $this->request->get('oauth_token');

        $consumer_key = $this->request->get('oauth_consumer_key');
        if ($token && $consumer_key) {
            /** @var IOauthConsumer|IOauthToken $token  */
            $token = $this->store->getServerToken( $consumer_key, $token, self::TOKEN_TYPE_ACCESS );
            $token->authorized = 0;
            $token->token_ttl = new \DateTime();
            $token->update();
        }
    }

    /**
     *
     * @param type $token_type
     * @return \Foundation\Oauth\Secrets
     */
    public function getCurrentSecrets($token_type = self::TOKEN_TYPE_ACCESS) {
        //if ($this->_cachedCurrentSecrets===null || $token_type != self::TOKEN_TYPE_ACCESS) {
        if ($token_type != self::TOKEN_TYPE_ACCESS) {
            $consumer_key = $this->request->get('oauth_consumer_key');
            $token = $this->request->get('oauth_token');
            $secrets = null;

            if (\is_array($token)) {
                $token = isset($token[0]) ? $token[0] : null;
                if ($token_type === false) {
                    $token_type = self::TOKEN_TYPE_ACCESS;
                }
            }

            if ($consumer_key && ($token_type === false || $token)) {
                try {
                    $secrets = $this->store->getSecretsForVerify(	$this->request->oauthurldecode($consumer_key),
                        $this->request->oauthurldecode($token),
                        $token_type);
                } catch (\Foundation\Oauth\OauthException $e) {
                    $secrets = null;
                }
            }

            if ($token_type != self::TOKEN_TYPE_ACCESS) {
                return $secrets;
            } else {
                //$_cachedCurrentSecrets = $secrets ? $secrets : false;
                return $secrets ? $secrets : false;
            }
        }
        //TODO co když to neni access? co vratim?
        //return $_cachedCurrentSecrets;
    }

    /**
     * Verify the request
     *
     * @param string token_type the kind of token needed, defaults to 'access' (false, 'access', 'request')
     * @exception OAuthException2 thrown when the request did not verify
     * @return Secrets
     *
     */
    public function verifyExtended ( $token_type = self::TOKEN_TYPE_ACCESS ){
        $consumer_key = $this->request->get('oauth_consumer_key');
        $token        = $this->request->get('oauth_token');
        $user_id      = false;
        $secrets      = array();

        if ($consumer_key && ($token_type === false || $token)){
            if (\is_array($token)) {
                $token = isset($token[0]) ? $token[0] : null;
                if ($token_type===false) {
                    $token_type = self::TOKEN_TYPE_ACCESS;
                }
            }

            $secrets = $this->getCurrentSecrets($token_type);

            if (!$secrets) {
                throw new OauthException('The consumer_key "'.$consumer_key.'" token "'.$token.'" combination does not exist or is not enabled.');
            }

            $this->store->checkServerNonce(	$this->request->oauthurldecode($consumer_key),
                $this->request->oauthurldecode($token),
                $this->request->getParam('oauth_timestamp', true),
                $this->request->getParam('oauth_nonce', true));

            $oauth_sig = $this->request->get('oauth_signature');
            if (empty($oauth_sig)) {
                throw new OauthException('Verification of signature failed (no oauth_signature in request).');
            }

            try {
                $this->request->verifySignature($secrets, $token_type);
            } catch (OauthException $e) {
                throw new OauthException('Verification of signature failed (signature base string was "'.$this->request->getSignatureBaseString().'").'
                    . " with  " . print_r(array($secrets['consumer_secret'], $secrets['token_secret'], $token_type), true));
            }

            // Check the optional body signature
            if ($this->request->get('xoauth_body_signature') && !($this->request->getContentType() == 'multipart/form-data')) {
                $method = $this->request->get('xoauth_body_signature_method');
                if (empty($method)) {
                    $method = $this->request->get('oauth_signature_method');
                }

                try {
                    $this->request->verifyDataSignature($secrets, $method, $this->request->get('xoauth_body_signature'), $this->request->getRequestBody());
                } catch (OauthException $e) {
                    //\Foundation\Utils\Logger::log("bad-body", $this->request->getMethod() . \var_export($this->request->getRequestBody(), true));
                    throw new OauthException('Verification of body signature failed.');
                }
            }

            // All ok - fetch the user associated with this request
            if ($secrets->account_id){
                $user_id = $secrets->account_id;
            }

            // Check if the consumer wants us to reset the ttl of this token
            $ttl = $this->request->getParam('xoauth_token_ttl', true);
            if (is_numeric($ttl)) {
                $this->store->updateConsumerAccessTokenTtl($this->urldecode($token), $ttl); //TODO urldecode - co to asi tak má dělat?
            }
        } else {
            throw new OauthException('Can\'t verify request, missing oauth_consumer_key or oauth_token');
        }

        return $secrets;
    }

    /**
     * Decode a string according to RFC3986.
     * Also correctly decodes RFC1738 urls.
     *
     * @param string s
     * @return string
     */
    public function oauthurldecode ( $s ){
        if ($s === false) {
            return $s;
        } else {
            return rawurldecode($s);
        }
    }



    /**
     * Handle the request_token request.
     * Returns the new request token and request token secret.
     *
     * TODO: add correct result code to exception
     *
     * @return string 	returned request token, false on an error
     */
    public function requestToken () {
        try {
            $this->verifyRequest(false);

            $options = array();
            $ttl     = $this->request->get('xoauth_token_ttl');
            if ($ttl)
            {
                $options['token_ttl'] = $ttl;
            }

            // 1.0a Compatibility : associate callback url to the request token
            $cbUrl   = $this->request->getParam('oauth_callback', true);
            if ($cbUrl) {
                $options['oauth_callback'] = $cbUrl;
            }

            // Create a request token
            $token  = $this->store->addConsumerRequestToken($this->request->getParam('oauth_consumer_key', true), $options);

            $this->response->setContent(array(
                "oauth_callback_confirmed"=>1,
                "oauth_token"=> $token->token,
                "oauth_token_secret" => $token->token_secret
            ));

            if ($token['used_token_ttl']){
                $this->response['xoauth_token_ttl'] = $token['used_token_ttl'];
            }

            $request_token = $token->token_ttl;

            $this->response->setStatusCode(200, "");
            $this->response->setContentType('application/x-www-form-urlencoded');

        } catch (OauthException $e) {
            $request_token = false;
            $this->response->setStatusCode(401, "OAuth Verification Failed: " . $e->getMessage());
        }

        return $this->response;
    }


    /**
     * Verify the start of an authorization request.  Verifies if the request token is valid.
     * Next step is the method authorizeFinish()
     *
     * Nota bene: this stores the current token, consumer key and callback in the _SESSION
     *
     * @exception OAuthException2 thrown when not a valid request
     * @return IOauthToken
     */
    public function authorizeVerify ($manualToken = null){

        $token = $manualToken ? $manualToken : $this->request->getParam('oauth_token', true);
        if (\is_array($token)) {
            $token = isset($token[0]) ? $token[0] : null;
        }
        $rs = $this->store->getConsumerRequestToken($token);

        if (!$rs){
            throw new OauthException('Unknown request token "'.$token.'"');
        }

        // We need to remember the callback
        $verify_oauth_token = $this->session['verify_oauth_token'];

        if ( empty($verify_oauth_token) && !$manualToken || strcmp($verify_oauth_token, $rs->token)){
            $this->session->set('verify_oauth_token', $rs->token);
            $this->session->set('verify_oauth_consumer_key', $rs->getOauthServerRegistry()->consumer_key);
            $cb = $this->request->getParam('oauth_callback', true);
            if ($cb)
                $this->session->set('verify_oauth_callback', $cb);
            else
                $this->session->set('verify_oauth_callback', $rs->callback_url);
        }
        return $rs;
    }


    /**
     * Overrule this method when you want to display a nice page when
     * the authorization is finished.  This function does not know if the authorization was
     * succesfull, you need to check the token in the database.
     *
     * @param boolean authorized	if the current token (oauth_token param) is authorized or not
     * @param int user_id			user for which the token was authorized (or denied)
     * @return string verifier  For 1.0a Compatibility
     */
    public function authorizeFinish ( $authorized, Account $account ){

        $token = $this->request->getParam('oauth_token', true);
        $verifier = null;
        if ($this->session->get('verify_oauth_token') == $token)
        {
            // Flag the token as authorized, or remove the token when not authorized
            $store = $this->store;

            // Fetch the referrer host from the oauth callback parameter
            $referrer_host  = '';
            $oauth_callback = false;
            $verify_oauth_callback = $this->session->get('verify_oauth_callback');
            if (!empty($verify_oauth_callback) && $verify_oauth_callback != 'oob') // OUT OF BAND
            {
                $oauth_callback = $this->session->get('verify_oauth_callback');
                $ps = parse_url($oauth_callback);
                if (isset($ps['host'])) {
                    $referrer_host = $ps['host'];
                }
            }

            if ($authorized) {
                //$this->logger->addNote('Authorized token "'.$token.'" for user '.$account->email.' with referrer "'.$referrer_host.'"');
                // 1.0a Compatibility : create a verifier code
                $verifier = $store->authorizeConsumerRequestToken($token, $account, $referrer_host);
            } else {
                //$this->logger->addNote('Authorization rejected for token "'.$token.'" for user '.$account->email."\nToken has been deleted");
                $store->deleteConsumerRequestToken($token);
            }

            if (!empty($oauth_callback)) {
                $params = array('oauth_token' => rawurlencode($token));

                // 1.0a Compatibility : if verifier code has been generated, add it to the URL
                if ($verifier) {
                    $params['oauth_verifier'] = $verifier;
                }

                $uri = preg_replace('/\s/', '%20', $oauth_callback);
                if (!empty($this->allowed_uri_schemes))
                {
                    if (!in_array(substr($uri, 0, strpos($uri, '://')), $this->allowed_uri_schemes))
                    {
                        throw new OauthException('Illegal protocol in redirect uri '.$uri);
                    }
                }
                else if (!empty($this->disallowed_uri_schemes))
                {
                    if (in_array(substr($uri, 0, strpos($uri, '://')), $this->disallowed_uri_schemes))
                    {
                        throw new OauthException('Illegal protocol in redirect uri '.$uri);
                    }
                }

                return $this->response->redirect($oauth_callback, $params);
            }
        }
    }

    public function xAuthAccessTokenForAccount (Account $account, IOauthConsumer $app){
        try{

            $options = array();
            $ttl     = $this->request->get('xoauth_token_ttl');

            if ($ttl){
                $options['token_ttl'] = $ttl;
            }

            $verifier = $this->request->get('oauth_verifier');
            if ($verifier) {
                $options['verifier'] = $verifier;
            }

            $store  = $this->store;
            $token  = $store->createAuthorizedAccessToken($account, $app, $options);


            $this->response->setContent(array(
                "oauth_token" => $token->token,
                "oauth_token_secret" => $token->token_secret
            ));

            if ($token->ttl) {
                $this->response['xoauth_token_ttl'] = $token->ttl;
            }

            $this->response->setStatusCode(200, "");
            $this->response->setContentType("application/x-www-form-urlencoded");

        } catch (OauthException $e) {
            $this->response->setStatusCode(401, "OAuth Verification Failed: " . $e->getMessage());
        }

        return $this->response;
    }

    /**
     * Exchange a request token for an access token.
     * The exchange is only succesful iff the request token has been authorized.
     *
     * Never returns, calls exit() when token is exchanged or when error is returned.
     */
    public function accessToken () {

        try {
            $this->verifyRequest(self::TOKEN_TYPE_REQUEST);

            $options = array();
            $ttl     = $this->request->get('xoauth_token_ttl');

            if ($ttl) {
                $options['token_ttl'] = $ttl;
            }

            $verifier = $this->request->get('oauth_verifier');
            if ($verifier) {
                $options['verifier'] = $verifier;
            }

            $store  = $this->store;
            $token  = $store->exchangeConsumerRequestForAccessToken($this->request->getParam('oauth_token', true), $options);

            $this->response->setContent(array(
                "oauth_token" => $token->token,
                "oauth_token_secret" => $token->token_secret
            ));

            if ($token->ttl) {
                $this->response['xoauth_token_ttl'] = $token->ttl;
            }

            $this->response->setStatusCode(200, "");
            $this->response->setContentType("application/x-www-form-urlencoded");

        } catch (OauthException $e) {
            $this->response->setStatusCode(401, "OAuth Verification Failed: " . $e->getMessage());
        }

        return $this->response;
    }
}