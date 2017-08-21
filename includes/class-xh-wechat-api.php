<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WShop_Wechat_Api{
    private $appid,$appsecret;
    public function __construct($appid,$appsecret){
        $this->appid = $appid;
        $this->appsecret = $appsecret;
    }
    /**
     * 获取用户 openid
     * @param string $appid
     * @param string $appsecret
     * @throws Exception
     * @return string|NULL|wp_redirect()
     * @since 1.0.0
     */
    public function get_openid(){
        $openid =null;
    
        //微信登录
        if(class_exists('XH_Social')){
            $wechat_login =XH_Social::instance()->channel->get_social_channel('social_wechat',array('login'));
            if($wechat_login&&method_exists($wechat_login, 'get_openid')){
                 
                $openid = $wechat_login->get_openid();
                if(!empty($openid)){
                    return $openid;
                }
            }
        }
    
        if (!isset($_GET['code'])){
            //触发微信返回code码
            $params = array();
            $params["appid"] = $this->appid;
            $params["redirect_uri"] = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            $params["response_type"] = "code";
            $params["scope"] = "snsapi_base";
            $params["state"] = "STATE";
             
            header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?".http_build_query($params)."#wechat_redirect");
            exit();
        } else {
            $params = array();
            $params["appid"] = $this->appid;
            $params["secret"] = $this->appsecret;
            $params["code"] = $_GET['code'];
            $params["grant_type"] = "authorization_code";
    
            $response = WShop_Helper_Http::http_get( "https://api.weixin.qq.com/sns/oauth2/access_token?".http_build_query($params));
            if(!$response){
                throw new Exception('invalid callback data when get openid.');
            }
    
            //取出openid
            $data = json_decode($response,true);
            if(!$data||(isset($data['errcode'])&&$data['errcode']!=0)){
                throw new Exception($response,$data&&isset($data['errcode'])?$data['errcode']:-1);
            }
    
            return isset($data['openid'])?$data['openid']:null;
        }
    }
    
}