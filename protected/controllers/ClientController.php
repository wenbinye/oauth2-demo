<?php
class ClientController extends CController
{
    public function getOauthAuthorizeUrl()
    {
        return 'http://'. Yii::app()->params['oauthServerHost'] . $this->createUrl('/oauth/authorize');
    }
    
    public function getOauthTokenUrl()
    {
        return 'http://'. Yii::app()->params['oauthServerHost'] . $this->createUrl('/oauth/access_token');
    }
    
    public function getResourceUrl($action)
    {
        return 'http://'. Yii::app()->params['oauthServerHost'] . $this->createUrl('/api/'. $action);
    }
    
    public function getOAuthClient()
    {
        $client_id = '1362053493';
        $client_secret = '900150983cd24fb0d6963f7d28e17f72';
        $client = new \OAuth2\Client($client_id, $client_secret);
        return $client;
    }
    
    public function actionIndex()
    {
        $redirect_uri = $this->createAbsoluteUrl('callback');
        $auth_url = $this->getOAuthClient()->getAuthenticationUrl($this->oauthAuthorizeUrl, $redirect_uri);
        $this->redirect($auth_url);
    }

    public function actionCallback()
    {
        $code = Yii::app()->request->getParam('code');
        if ( $code ) {
            $client = $this->getOAuthClient();
            $response = $client->getAccessToken($this->oauthTokenUrl, 'authorization_code', array(
                'code' => $code,
                'redirect_uri' => $this->createAbsoluteUrl('callback')
            ));
            if ( isset($response['result']['error']) ) {
                Yii::log($response['result']['error_description'], "error");
                echo $response['result']['error_description'];
                return;
            }
            $token = $response['result'];
            Yii::app()->session['oauth_token'] = array(
                'access_token' => $token['access_token'],
                'expires' => $token['expires_in'] + time() - 60
            );
            $client->setAccessToken($token['access_token']);
            $response = $client->fetch($this->getResourceUrl('me'));
            var_dump($response);
        }
    }

    public function actionMe()
    {
        if ( isset(Yii::app()->session['oauth_token']['access_token']) ) {
            $client = $this->getOAuthClient();
            $client->setAccessToken(Yii::app()->session['oauth_token']['access_token']);
            $response = $client->fetch($this->getResourceUrl('me'));
            var_dump($response);
            // echo $response['result'];
        } else {
            $this->forward('index');
        }
    }
}
