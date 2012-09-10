<?php
/**
* Instagr.am API wrapper class
* 
* @author Ilya Chekalskiy <ilya@chekalskiy.ru>
* @author Jaisen Mathai <jaisen@jmathai.com>
* 
* @version 0.9.3
* @link http://instagram.com/developer
* @link http://github.com/jmathai/php-multi-curl
*/
class CheInstagram {
    protected $clientId, $clientSecret, $accessToken;
    protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token';
    protected $authorizeUrl   = 'https://api.instagram.com/oauth/authorize';
    protected $apiUrl         = 'https://api.instagram.com';
    protected $userAgent      = 'Instagram PHP ilya@chekalskiy.ru';
    protected $apiVersion     = 'v1';
    protected $isAsynchronous = false;
    protected $followLocation = false;
    protected $connectionTimeout = 5;
    protected $requestTimeout = 30;
    protected $debug = false;
    
    public function __construct($clientId = null, $clientSecret = null, $accessToken = null) {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->setAccessToken($accessToken);
    }
    
    /**
     * Getting user's access token after authorization
     * 
     * @param string $code Content of «code» GET parameter
     * @param string $redirectUri
     * @return InstagramJson
     */
    public function getAccessToken($code, $redirectUri) {
        $params = array(
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri,
            'code'          => $code
        );

        return $this->request('POST', $this->accessTokenUrl, $params);
    }

    /**
     * Getting url for user's authorization
     * 
     * @link http://instagram.com/developer/auth/#scope
     * @param string $redirectUri 
     * @param array $scope Permissions
     * @return string
     */
    public function getAuthorizeUrl($redirectUri, $scope = array()) {
        if ($scope && count($scope))
            $scope = array('scope' => implode(' ', $scope));
        else $scope = array();
        
        $params = array_merge(array('client_id' => $this->clientId, 'response_type' => 'code', 'redirect_uri' => $redirectUri), $scope);
        
        $qs = http_build_query($params);
        return "{$this->authorizeUrl}?{$qs}";
    }

    public function setAccessToken($accessToken) {
        if (!empty($accessToken))
          $this->accessToken = $accessToken;
    }

    public function setTimeout($requestTimeout = null, $connectionTimeout = null) {
        if ($requestTimeout !== null)
          $this->requestTimeout = floatval($requestTimeout);
        if ($connectionTimeout !== null)
          $this->connectionTimeout = floatval($connectionTimeout);
    }
    
    public function setUserAgent($agent) {
        $this->userAgent = $agent;
    }

    public function useApiVersion($version = null) {
        $this->apiVersion = $version;
    }

    public function useAsynchronous($async = true) {
        $this->isAsynchronous = (bool)$async;
    }
    
    // Public api interface for most calls GET/POST/DELETE
    public function delete($endpoint, $params = null) {
        return $this->request('DELETE', $endpoint, $params);
    }

    public function get($endpoint, $params = null) {
        return $this->request('GET', $endpoint, $params);
    }

    public function post($endpoint, $params = null) {
        return $this->request('POST', $endpoint, $params);
    }
    
    private function getApiUrl($endpoint) {
        if (!empty($this->apiVersion))
          return "{$this->apiUrl}/{$this->apiVersion}{$endpoint}";
        else
          return "{$this->apiUrl}{$endpoint}";
    }

    private function request($method, $endpoint, $params = null) {
        if (preg_match('#^https?://#', $endpoint))
            $url = $endpoint;
        else
            $url = $this->getApiUrl($endpoint);

        if ($this->accessToken) {
            $params['access_token'] = $this->accessToken;
        }
            
        $params['client_id']     = $this->clientId;
        $params['client_secret'] = $this->clientSecret;
        
        if($method === 'GET' || $method === 'DELETE')
            $url .= is_null($params) ? '' : '?'.http_build_query($params, '', '&');
                    
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if (isset($_SERVER ['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1')
            curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER ['SERVER_ADDR']);
        
        if ($method === 'POST' && $params !== null)
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $resp = new InstagramJson(EpiCurl::getInstance()->addCurl($ch), $this->debug);
        if (!$this->isAsynchronous)
            $resp->responseText;

        return $resp;
    }
}


class InstagramJson implements ArrayAccess, Countable, IteratorAggregate {
  private $debug;
  private $__resp;
  public function __construct($response, $debug = false)
  {
    $this->__resp = $response;
    $this->debug  = $debug;
  }

  // ensure that calls complete by blocking for results, NOOP if already returned
  public function __destruct()
  {
    $this->responseText;
  }

  // Implementation of the IteratorAggregate::getIterator() to support foreach ($this as $...)
  public function getIterator ()
  {
    if ($this->__obj) {
      return new ArrayIterator($this->__obj);
    } else {
      return new ArrayIterator($this->response);
    }
  }

  // Implementation of Countable::count() to support count($this)
  public function count ()
  {
    return count($this->response);
  }
  
  // Next four functions are to support ArrayAccess interface
  // 1
  public function offsetSet($offset, $value) 
  {
    $this->response[$offset] = $value;
  }

  // 2
  public function offsetExists($offset) 
  {
    return isset($this->response[$offset]);
  }
  
  // 3
  public function offsetUnset($offset) 
  {
    unset($this->response[$offset]);
  }

  // 4
  public function offsetGet($offset) 
  {
    return isset($this->response[$offset]) ? $this->response[$offset] : null;
  }

  public function __get($name)
  {
    $accessible = array('responseText'=>1,'headers'=>1,'code'=>1);
    $this->responseText = $this->__resp->data;
    $this->headers      = $this->__resp->headers;
    $this->code         = $this->__resp->code;
    if(isset($accessible[$name]) && $accessible[$name])
      return $this->$name;
    elseif(($this->code < 200 || $this->code >= 400) && !isset($accessible[$name]))
      InstagramException::raise($this->__resp, $this->debug);

    // Call appears ok so we can fill in the response
    $this->response     = json_decode($this->responseText, 1);
    $this->response     = $this->response;
    $this->__obj        = json_decode($this->responseText);
    $this->__obj        = $this->__obj;

    if(gettype($this->__obj) === 'object')
    {
      foreach($this->__obj as $k => $v)
      {
        $this->$k = $v;
      }
    }

    if (property_exists($this, $name)) {
      return $this->$name;
    }
    return null;
  }

  public function __isset($name)
  {
    $value = self::__get($name);
    return !empty($name);
  }
}

class InstagramException extends Exception{
  public static function raise($response, $debug) {
    $error = json_decode($response->data, true);
    if (isset($error['meta'])) {
        $message = $error['meta']['error_message'];
        $code = $error['meta']['code'];
    } elseif (isset($error['error_message'])) {
        $message = $error['error_message'];
        $code = $error['code'];
    } elseif (is_string($response->data)) {
        $code = $response->code;
        $message = $response->data;
    } else {
        $code = $response->code;
        $message = 'Unknown error';
    }
 
    switch($code) {
      case 400:
        throw new InstagramBadRequestException($message, $code);
      case 401:
        throw new InstagramNotAuthorizedException($message, $code);
      case 403:
        throw new InstagramForbiddenException($message, $code);
      case 404:
        throw new InstagramNotFoundException($message, $code);
      default:
        throw new InstagramException($message, $code);
    }
  }
}
class InstagramBadRequestException extends InstagramException{}
class InstagramNotAuthorizedException extends InstagramException{}
class InstagramForbiddenException extends InstagramException{}
class InstagramNotFoundException extends InstagramException{}