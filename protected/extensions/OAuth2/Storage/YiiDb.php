<?php
namespace OAuth2\Storage;

class YiiDb implements
    \OAuth2_Storage_AuthorizationCodeInterface,
    \OAuth2_Storage_UserCredentialsInterface,
    \OAuth2_Storage_AccessTokenInterface,
    \OAuth2_Storage_ClientCredentialsInterface,
    \OAuth2_Storage_RefreshTokenInterface,
    \OAuth2_Storage_JWTBearerInterface
{
    protected $config;

    private $db;

    public function __construct($connectionID='db', $config=array())
    {
        if ( $connectionID instanceof \CDbConnection ) {
            $this->db = $connectionID;
        } else {
            $this->db = \Yii::app()->getComponent($connectionID);
			if( !$this->db instanceof \CDbConnection ) {
				throw new \Exception('\OAuth2\Storage\YiiDb.connectionID is invalid. '
                                     . 'Please make sure it refers to the ID of a CDbConnection application component.');
            }
        }
        $this->config = array_merge(array(
            'client_table' => 'oauth_clients',
            'access_token_table' => 'oauth_access_tokens',
            'refresh_token_table' => 'oauth_refresh_tokens',
            'code_table' => 'oauth_authorization_codes',
            'user_table' => 'oauth_users',
            'jwt_table' => 'oauth_jwt',
        ), $config);
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
        return $this->db->createCommand()
            ->from($this->config['client_table'])
            ->where('client_id = :client_id', array(':client_id'=>$client_id))
            ->queryRow();
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
        $token = $this->db->createCommand()
            ->from($this->config['access_token_table'])
            ->where('access_token = :access_token', array(':access_token' => $access_token))
            ->queryRow();
        if ( $token ) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
        }
        return $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $row = compact('access_token', 'client_id', 'user_id', 'expires', 'scope');

        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            unset($row['access_token']);
            return (bool)$this->db->createCommand()
                ->update(
                    $this->config['access_token_table'],
                    $row,
                    'access_token=:access_token',
                    array(':access_token'=>$access_token)
                );
        } else {
            return (bool)$this->db->createCommand()
                ->insert($this->config['access_token_table'], $row);
        }
    }

    /* AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        $result = $this->db->createCommand()
            ->from($this->config['code_table'])
            ->where('authorization_code = :code', array(':code'=>$code))
            ->queryRow();
        if ( $result ) {
            // convert date string back to timestamp
            $result['expires'] = strtotime($result['expires']);
        }
        return $result;
    }

    public function setAuthorizationCode($authorization_code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $row = compact('authorization_code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope');

        // if it exists, update it.
        if ($this->getAuthorizationCode($authorization_code)) {
            unset($row['authorization_code']);
            return (bool)$this->db->createCommand()
                ->update(
                    $this->config['code_table'],
                    $row,
                    'authorization_code=:code',
                    array(':code'=>$authorization_code)
                );
        } else {
            return (bool)$this->db->createCommand()
                ->insert($this->config['code_table'], $row);
        }
    }

    public function expireAuthorizationCode($code)
    {
        return (bool)$this->db->createCommand()
            ->delete($this->config['code_table'], 'authorization_code = :code', array(':code'=>$code));
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
        $result = $this->db->createCommand()
            ->from($this->config['refresh_token_table'])
            ->where('refresh_token = :refresh_token', array(':refresh_token' => $refresh_token))
            ->queryRow();
        if ( $result ) {
            // convert expires to epoch time
            $result['expires'] = strtotime($result['expires']);
        }
        return $result;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $row = compact('refresh_token', 'client_id', 'user_id', 'expires', 'scope');
        return (bool)$this->db->createCommand()
            ->insert($this->config['refresh_token_table'], $row);
    }

    public function unsetRefreshToken($refresh_token)
    {
        return (bool)$this->db->createCommand()
            ->delete($this->config['refresh_token_table'], 'refresh_token = :refresh_token', array(':refresh_token'=>$refresh_token));
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        return $user['password'] == $password;
    }

    public function getUser($username)
    {
        return $this->db->createCommand()
            ->from($this->config['user_table'])
            ->where('username=:username', array(':username'=>$username))
            ->queryRow();
    }

    public function setUser($username, $password, $first_name = null, $last_name = null)
    {
        $row = compact('username', 'password', 'first_name', 'last_name');
        // if it exists, update it.
        if ($this->getUser($username)) {
            unset($row['username']);
            return (bool)$this->db->createCommand()
                ->update($this->config['user_table'], $row, 'username=:username',array(':username'=>$username));
        } else {
            return (bool)$this->db->createCommand()
                ->insert($this->config['user_table'], $row);
        }
    }

    public function getClientKey($client_id, $subject)
    {
        $result = $this->db->createCommand()
            ->from($this->config['jwt_table'])
            ->where('client_id=:client_id AND subject=:subject', array(':client_id'=>$client_id, ':subject'=>$subject))
            ->queryRow();
        return $result;
    }
}
