<?php

namespace Zveen\TwitterBundle\Services;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;

class Twitter {
    
    /**
    * Twitter application consumer key
    * @var string 
    */
    private $consumerKey;
    
    /**
    * Twitter application consumer secret
    * @var string 
    */
    private $consumerSecret;
    
    /**
    * Twitter OAuth request token url.
    * 
    * Default value: http://twitter.com/oauth/request_token
    * @var string 
    */
    private $requestTokenUrl;
    
    /**
    * Twitter OAuth access token url.
    * 
    * Default value: http://twitter.com/oauth/access_token
    * @var string 
    */
    private $accessTokenUrl;
    
    /**
    * Twitter OAuth auth url.
    * 
    * Default value: http://twitter.com/oauth/access_token
    * @var string 
    */
    private $authUrl;
    
    /**
    * Symfony Session
    * @var Session 
    */
    private $session;
    
    /**
    * Debug flag
    * @var boolean
    */
    private $debug;
    
    /**
    * Check SSL Certificates
    * @var boolean
    */
    private $checkSSL;
    
	
    public function __construct($config, Session $session) {
        $this->consumerKey = $config['consumerKey'];
        $this->consumerSecret = $config['consumerSecret'];
        $this->requestTokenUrl = $config['requestTokenUrl'];
        $this->accessTokenUrl = $config['accessTokenUrl'];
        $this->authUrl = $config['authUrl'];
        $this->debug = $config['debug'];
        $this->checkSSL = $config['checkSSL'];
        
        $this->session = $session;
    }
    
    /**
     * Returns if we are able to query Twitter API
     * @return boolean
     */
    public function canQueryAPI(){
        return $this->session->has('zveen_twitter.oauthAccessToken');
    }
	
	/**
     * Returns if login is canceled
     * @return boolean
     */
    public function isLoginDenied($request){
        return $request->request->has('denied');
    }
    
    /**
     * Cleans the internal storage
     */
    private function cleanStorage(){
        $this->session->remove('zveen_twitter.requestTokenSecret');
        $this->session->remove('zveen_twitter.oauthAccessToken');
        $this->session->remove('zveen_twitter.oauthAccessTokenSecret');
    }

    /**
     * Initializes the OAuth client with correct tokens and bundle configuration
     * 
     * @return \OAuth
     */
    private function initOAuth(){
        $oauthClient = new \OAuth($this->consumerKey, $this->consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauthClient->setNonce(rand());
        
        $this->checkSSL? $oauthClient->enableSSLChecks():$oauthClient->disableSSLChecks();
        $this->debug? $oauthClient->enableDebug():$oauthClient->disableDebug();
        
        if($this->session->has('zveen_twitter.oauthAccessToken') && $this->session->has('zveen_twitter.oauthAccessTokenSecret')){
            $oauthClient->setToken(
                $this->session->get('zveen_twitter.oauthAccessToken'),
                $this->session->get('zveen_twitter.oauthAccessTokenSecret')
            );
        }
        return $oauthClient;
    }
    
    /**
     * Depending on the state of the OAuth flow this function either returns redirect url
     * or null if we successfuly finished the OAuth flow. See README of the bundle for usage.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|null
     */
    public function handleLogin(Request $request){
        $oauthClient = $this->initOAuth();
        
        if(!$this->session->has('zveen_twitter.requestTokenSecret')){
            // Initiate token request
            $requestTokenInfo = $oauthClient->getRequestToken($this->requestTokenUrl);
            $this->session->set('zveen_twitter.requestTokenSecret', $requestTokenInfo['oauth_token_secret']);
            return $this->authUrl . '?oauth_token=' . $requestTokenInfo['oauth_token'];
        }else if(!$this->session->has('zveen_twitter.oauthAccessToken')){
            // Trade token for accessToken
            $requestTokenSecret = $this->session->get('zveen_twitter.requestTokenSecret');
            $oauthClient->setToken($request->query->get('oauth_token'), $requestTokenSecret);
            try{
                $accessTokenInfo = $oauthClient->getAccessToken($this->accessTokenUrl);
            }catch(\OAuthException $exception){
                // Retry OAuth flow
                $this->cleanStorage();
                $requestTokenInfo = $oauthClient->getRequestToken($this->requestTokenUrl);
                $this->session->set('zveen_twitter.requestTokenSecret', $requestTokenInfo['oauth_token_secret']);
                return $this->authUrl . '?oauth_token=' . $requestTokenInfo['oauth_token'];
            }
            $this->session->set('zveen_twitter.oauthAccessToken', $accessTokenInfo['oauth_token']);
            $this->session->set('zveen_twitter.oauthAccessTokenSecret', $accessTokenInfo['oauth_token_secret']);
            $this->session->remove('zveen_twitter.requestTokenSecret');
            return null;
        }
    }
    
    /**
     * Calls the REST api GET endpoint
     * 
     * @param string $url
     * @return \Zveen\TwitterBundle\Services\TwitterApiResult
     */
    public function apiGet($url){
        return $this->apiQuery($url);
    }
    
    /**
     * Calls the REST api POST endpoint
     * 
     * @param string $url
     * @param array $data 
     * @return \Zveen\TwitterBundle\Services\TwitterApiResult
     */
    public function apiPost($url,$data = array()){
        return $this->apiQuery($url, $data, OAUTH_HTTP_METHOD_POST);
    }
    
    /**
     * Uses OAuth client to call the REST api. 
     * 
     * @param string $url
     * @param array $data
     * @param string $method
     * @return \Zveen\TwitterBundle\Services\TwitterApiResult
     */
    private function apiQuery($url, $data = array(), $method = OAUTH_HTTP_METHOD_GET){
        $oauthClient = $this->initOAuth();
        $result = new TwitterApiResult();
        try{
            $oauthClient->fetch($url, $data, $method);
            $result->error = false;
        }
        catch(\OAuthException $exception){ 
            $result->error = true;
            $result->exception = $exception;
            $response = json_decode($oauthClient->getLastResponse());
            $result->errorMessage = $response->errors[0]->message;
        }
        
        $result->lastResponse = $oauthClient->getLastResponse();
        $result->lastResponseHeaders = $oauthClient->getLastResponseHeaders();
        $result->lastResponseInfo = $oauthClient->getLastResponseInfo();
        
        return $result;
    }
    
}

class TwitterApiResult{
    public $error;
    public $errorMessage;
    public $exception;
    public $lastResponse;
    public $lastResponseHeaders;
    public $lastResponseInfo;
    
}