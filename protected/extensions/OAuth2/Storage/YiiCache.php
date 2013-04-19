<?php
namespace OAuth2\Storage;

class YiiCache implements
    \OAuth2_Storage_AuthorizationCodeInterface,
    \OAuth2_Storage_UserCredentialsInterface,
    \OAuth2_Storage_AccessTokenInterface,
    \OAuth2_Storage_ClientCredentialsInterface,
    \OAuth2_Storage_RefreshTokenInterface,
    \OAuth2_Storage_JWTBearerInterface
{
    private $cache;
    private $separator;
    private $prefix;
    
    public function __construct($cacheID='cache', $config=array(), $ns_separator='/')
    {
        if ( $cacheID instanceof \ICache ) {
            $this->cache = $cacheID;
        } else {
            $this->cache = \Yii::app()->getComponent($cacheID);
			if( !$this->cache instanceof \ICache ) {
				throw new \Exception('\OAuth2\Storage\YiiCache.cacheID is invalid. '
                                     . 'Please make sure it refers to the ID of a CCache application component.');
            }
        }
        $this->config = array_merge(array(
            'client_key' => 'oauth_clients',
            'access_token_key' => 'oauth_access_tokens',
            'refresh_token_key' => 'oauth_refresh_tokens',
            'code_key' => 'oauth_authorization_codes',
            'user_key' => 'oauth_users',
            'jwt_key' => 'oauth_jwt',
        ), $config);
        $this->seperator = $ns_separator;
    }
    
    /* ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $result = $this->getClientDetails($client_id);
        if ( $result ) {
            // make this extensible
            return $result['client_secret'] == $client_secret;
        } else {
            return false;
        }
    }

    public function getClientDetails($client_id)
    {
        return $this->cache->get($this->config['client_key'].$this->seperator.$client_id);
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, (array) $details['grant_types']);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        return $this->cache->get($this->config['access_token_key'].$this->seperator.$access_token);
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        $row = compact('access_token', 'client_id', 'user_id', 'expires', 'scope');
        return (bool)$this->cache->set($this->config['access_token_key'].$this->seperator.$access_token, $row, $expires-time());
    }

    /* AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        return $this->cache->get($this->config['code_key'].$this->seperator.$code);
    }

    public function setAuthorizationCode($authorization_code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        $row = compact('authorization_code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope');
        return (bool)$this->cache->set($this->config['code_key'].$this->seperator.$authorization_code, $row, $expires-time());
    }

    public function expireAuthorizationCode($code)
    {
        return (bool)$this->cache->delete($this->config['code_key'].$this->seperator.$code);
    }

    /* UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }
        return false;
    }

    public function getUserDetails($username)
    {
        return $this->getUser($username);
    }

    /* RefreshTokenInterface */
    public function getRefreshToken($refresh_token)
    {
        return $this->cache->get($this->config['refresh_token_key'].$this->seperator.$refresh_token);
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        $row = compact('refresh_token', 'client_id', 'user_id', 'expires', 'scope');
        return (bool)$this->cache->set($this->config['refresh_token_key'].$this->seperator.$refresh_token, $row, $expires-time());
    }

    public function unsetRefreshToken($refresh_token)
    {
        return (bool)$this->cache->delete($this->config['refresh_token_key'].$this->seperator.$refresh_token);
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $password;
    }

    public function getUser($username)
    {
        return $this->cache->get($this->config['user_key'].$this->seperator.$username);
    }

    public function setUser($username, $password, $first_name = null, $last_name = null)
    {
        $row = compact('username', 'password', 'first_name', 'last_name');
        return (bool)$this->cache->set($this->config['user_key'].$this->seperator.$username, $row);
    }

    public function getClientKey($client_id, $subject)
    {
        return $this->cache->get($this->config['jwt_key'].$this->seperator.$client_id.$this->seperator.$subject);
    }
}
