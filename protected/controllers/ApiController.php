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
        if ( Yii::app()->oauthServer->verifyResourceRequest($request) ) {
            $this->tokenData = Yii::app()->oauthServer->getAccessTokenData($request);
            $filterChain->run();
        } else {
            Yii::app()->oauthServer->getResponse()->send();
        }
    }
}

