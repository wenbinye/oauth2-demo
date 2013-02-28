<?php
class ApiController extends CController
{
    protected $tokenData;
    
    public function filters()
    {
        return array(
            'accessToken'
        );
    }

    public function actionMe()
    {
        echo json_encode(array(
            'username' => $this->tokenData['user_id']
        ));
    }

    public function filterAccessToken($filterChain)
    {
        $request = OAuth2_Request::createFromGlobals();
        $this->tokenData = Yii::app()->oauthServer->getAccessTokenData($request);
        if ( !$this->tokenData ) {
            Yii::app()->oauthServer->getResponse()->send();
        } else {
            $filterChain->run();
        }
    }
}

