<?php
class OauthController extends Controller
{
    public function actionAuthorize()
    {
        $request = Yii::app()->request;
        if ( Yii::app()->user->isGuest ) {
            // 跳转到登录页面
            Yii::app()->user->setReturnUrl($request->getRequestUri());
            $this->forward('/site/login');
        }

        if ( Yii::app()->request->isPostRequest ) {
            Yii::app()->oauthServer->handleAuthorizeRequest(
                OAuth2_Request::createFromGlobals(),
                (bool) $request->getParam('authorize'),
                Yii::app()->user->name
            )->send();
        } else {
            // 用户授权
            $this->render('authorize');
        }
    }

    public function actionAccess_Token()
    {
        $response = Yii::app()->oauthServer->handleGrantRequest(OAuth2_Request::createFromGlobals());
        $response->send();
    }
}
