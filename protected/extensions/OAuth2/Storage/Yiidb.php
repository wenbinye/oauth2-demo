<?php
namespace OAuth2\Storage;

class Yiidb implements
    \OAuth2_Storage_AuthorizationCodeInterface,
    \OAuth2_Storage_UserCredentialsInterface,
    \OAuth2_Storage_AccessTokenInterface,
    \OAuth2_Storage_ClientCredentialsInterface,
    \OAuth2_Storage_RefreshTokenInterface,
    \OAuth2_Storage_JWTBearerInterface,
    \OAuth2_Storage_ScopeInterface
{
    protected $connectionID;
    protected $memoize;
    protected $config;

    private $db;
    private $cache = array();

    public function __construct($connectionID='db', $config=array(), $memoize=true)
    {
        $this->connectionID = $connectionID;
        $this->config = array_merge(array(
            'client_table' => 'oauth_clients',
            'access_token_table' => 'oauth_access_tokens',
            'refresh_token_table' => 'oauth_refresh_tokens',
            'code_table' => 'oauth_authorization_codes',
            'user_table' => 'oauth_users',
            'jwt_table' => 'oauth_jwt',
        ), $config);
        $this->memoize = $memoize;
    }

    protected function getDbConnection()
    {
		if($this->db!==null) {
			return $this->db;
        } else if(($id=$this->connectionID)!==null) {
			if(($this->db=\Yii::app()->getComponent($id)) instanceof CDbConnection) {
				return $this->db;
            } else {
				throw new \Exception('\Ivy\OAuth2\Storage\Yiidb.connectionID is invalid. Please make sure it refers to the ID of a CDbConnection application component.');
            }
		}
    }

    /* ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        if ( $this->memoize && isset($this->cache['clientCredentials'][$client_id]) ) {
            $result = $this->cache['clientCredentials'][$client_id];
        } else {
            $result = $this->getDbConnection()->createCommand()
                ->select($this->config['client_table'])
                ->where('client_id = :client_id', array(':client_id'=>'client_id'))
                ->queryRow();
            if ( $this->memoize ) {
                $this->cache['clientCredentials'][$client_id] = $result;
            }
        }
        // make this extensible
        return $result['client_secret'] == $client_secret;
    }

    public function getClientDetails($client_id)
    {
        if ( $this->memoize && isset($this->cache['clientCredentials'][$client_id]) ) {
            $result = $this->cache['clientCredentials'][$client_id];
        } else {
            $result = $this->getDbConnection()->createCommand()
                ->select($this->config['client_table'])
                ->where('client_id = :client_id', array(':client_id'=>'client_id'))
                ->queryRow();
            if ( $this->memoize ) {
                $this->cache['clientCredentials'][$client_id] = $result;
            }
        }
        return $result;
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
        if ( $this->memoize && isset($this->cache['accessTokens'][$access_token]) ) {
            $token = $this->cache['accessTokens'][$access_token];
        } else {
            $token = $this->getDbConnection()->createCommand()
                ->select($this->config['access_token_table'])
                ->where('access_token = :access_token', array(':access_token' => $access_token))
                ->queryRow();
            if ( $token ) {
                // convert date string back to timestamp
                $token['expires'] = strtotime($token['expires']);
            }
            if ( $this->memoize ) {
                $this->cache['accessTokens'][$access_token] = $token;
            }
        }
        return $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $row = compact('access_token', 'client_id', 'user_id', 'expires', 'scope')

        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            unset($row['access_token']);
            return $this->getDbConnection()->createCommand()->update($this->config['access_token_table'], $row, 'access_token=:access_token', array(':access_token'=>$access_token));
        } else {
            return $this->getDbConnection()->createCommand()->insert($this->config['access_token_table'], $row);
        }
    }

    /* AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        if ( $this->memoize && isset($this->cache['authorizationCodes'][$code]) ) {
            $result = $this->cache['authorizationCodes'][$code];
        } else {
            $result = $this->getDbConnection()->createCommand()
                ->select($this->config['code_table'])
                ->where('authorization_code = :code', array(':code'=>$code))
                ->queryRow();
            if ( $result ) {
                // convert date string back to timestamp
                $result['expires'] = strtotime($result['expires']);
            }
            if ( $this->memoize ) {
                $this->cache['authorizationCodes'][$code] = $result;
            }
        }
        return $result;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $row = compact('code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope');

        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            return $this->getDbConnection()->createCommand()->update()
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_id=:client_id, user_id=:user_id, redirect_uri=:redirect_uri, expires=:expires, scope=:scope where authorization_code=:code', $this->config['code_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (authorization_code, client_id, user_id, redirect_uri, expires, scope) VALUES (:code, :client_id, :user_id, :redirect_uri, :expires, :scope)', $this->config['code_table']));
        }
        return $stmt->execute();
    }

    public function expireAuthorizationCode($code)
    {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE authorization_code = :code', $this->config['code_table']));

        return $stmt->execute(compact('code'));
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
        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE refresh_token = :refresh_token', $this->config['refresh_token_table']));

        $token = $stmt->execute(compact('refresh_token'));
        if ($token = $stmt->fetch()) {
            // convert expires to epoch time
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $stmt = $this->db->prepare(sprintf('INSERT INTO %s (refresh_token, client_id, user_id, expires, scope) VALUES (:refresh_token, :client_id, :user_id, :expires, :scope)', $this->config['refresh_token_table']));

        return $stmt->execute(compact('refresh_token', 'client_id', 'user_id', 'expires', 'scope'));
    }

    public function unsetRefreshToken($refresh_token)
    {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE refresh_token = :refresh_token', $this->config['refresh_token_table']));

        return $stmt->execute(compact('refresh_token'));
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $password;
    }

    public function getUser($username)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', $this->config['user_table']));
        $stmt->execute(array('username' => $username));
        return $stmt->fetch();
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        // if it exists, update it.
        if ($this->getUser($username)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName where username=:username', $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password, first_name, last_name) VALUES (:username, :password, :firstName, :lastName)', $this->config['user_table']));
        }
        return $stmt->execute(compact('username', 'password', 'firstName', 'lastName'));
    }

    public function getClientKey($client_id, $subject)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT public_key from %s where client_id=:client_id AND subject=:subject', $this->config['jwt_table']));

        $stmt->execute(array('client_id' => $client_id, 'subject' => $subject));
        return $stmt->fetch();
    }
}
