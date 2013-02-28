<?php
class OauthServer extends CApplicationComponent
{
    private $server;
    
    public function init()
    {
        $db = Yii::app()->db->getPdoInstance();
        $storage = new OAuth2_Storage_Pdo($db);
        $server = new OAuth2_Server($storage, array(
            'allow_implicit' => true,
            // 'token_bearer_header_name' => 'OAuth2'
        ));
        $server->addGrantType(new OAuth2_GrantType_AuthorizationCode($storage));
        $server->addGrantType(new OAuth2_GrantType_RefreshToken($storage));
        $this->server = $server;
    }

    public function __call($name, $args)
    {
        if ( method_exists($this->server, $name) ) {
            return call_user_func_array(array($this->server, $name), $args);
        } else {
            return parent::__call($name, $args);
        }
    }
}
