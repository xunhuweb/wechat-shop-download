<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

class WShop_Payment_Gateway_Wpopen_Wechat extends Abstract_WShop_Payment_Gateway{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Payment_Gateway_Wechat
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return WShop_Payment_Gateway_Wpopen_Wechat
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='wpopen_wechat';
        $this->group = 'wechat';
        $this->title=__('Wechat Pay',WSHOP);
        $this->description ='当前支付插件专为个人用户使用，如果您是企业用户，请使用 <a href="https://www.wpweixin.net" target="_blank">企业版插件</a>';
        $this->icon=WSHOP_URL.'/assets/image/wechat-l.png';
        $this->icon_small=WSHOP_URL.'/assets/image/wechat.png';
        
        $this->init_form_fields ();
        $this->enabled ='yes'==$this->get_option('enabled');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Abstract_WShop_Settings::init_form_fields()
     */
    public function init_form_fields() {
        $appid ='20146123713';
        $appsecret ='6D7B025B8DD098C485F0805193136FB9';
        $this->form_fields = array (
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', WSHOP ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable wechat payment', WSHOP ),
                'default' => 'no'
            ),
            'appid' => array (
                'title' => __ ( 'APP ID', WSHOP ),
                'type' => 'text',
                'description' => '测试账户仅支持1元内价格',
                'required' => true,
                'default'=>$appid,
                'css' => 'width:400px'
            ),
            'appsecret' => array (
                'title' => __ ( 'APP Secret', WSHOP ),
                'type' => 'text',
                'css' => 'width:400px',
                'required' => true,
                'default'=>$appsecret,
                'desc_tip' => false
            ),
            'gateway_url' => array (
                'title' => __ ( 'Transaction Url', WSHOP ),
                'type' => 'text',
                'css' => 'width:400px',
                'required' => true,
                'default'=>'http://pay2.xunhupay.com/v2',
                'desc_tip' => false,
                'description'=>'个人支付宝/微信即时到账，支付网关：https://pay.xunhupay.com  <a href="https://mp.xunhupay.com" target="_blank">获取Appid</a> <br/>
                                                  微信支付宝代收款，需提现，支付网关：https://pay.wordpressopen.com <a href="http://mp.wordpressopen.com " target="_blank">获取Appid</a>'
            )
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_WShop_Payment_Gateway::process_payment()
     */
    public function process_payment($order)
    {
        if(!$order->can_pay()){
            return WShop_Error::error_custom(__('Current order is paid or expired!',WSHOP));
        }
        //创建订单支付编号
        $sn = $order->generate_sn();
        if($sn instanceof WShop_Error){
            return $sn;
        }
        $exchange_rate = round(floatval(WShop_Settings_Default_Basic_Default::instance()->get_option('exchange_rate')),3);
        if($exchange_rate<=0){
            $exchange_rate = 1;
        }
        $data=array(
            'version'   => '1.1',//api version
            'lang'       => get_option('WPLANG','zh-cn'),
            'is_app'    => WShop_Helper_Uri::is_wechat_app()?'Y':'N',
            'plugins'   => 'wshop-wechat',
            'appid'     => $this->get_option('appid'),
            'trade_order_id'=> $sn,
            'payment'   => 'wechat',
            'total_fee' => round($order->get_total_amount(false)*$exchange_rate,2),
            'title'     => $order->get_title(),
            'description'=> null,
            'time'      => time(),
            'notify_url'=> home_url('/'),
            'return_url'=> $order->get_received_url(),
            'callback_url'=>isset($order->metas['location'])&&!empty($order->metas['location'])?$order->metas['location']:$order->get_received_url(),
            'nonce_str' => str_shuffle(time())
        );
        
        $hashkey          = $this->get_option('appsecret');
        $data['hash']     = $this->generate_xh_hash($data,$hashkey);
        $url              = $this->get_option('gateway_url').'/payment/do.html';
        
        try {
            $response     = WShop_Helper_Http::http_post($url, json_encode($data));
            $result       = $response?json_decode($response,true):null;
            if(!$result){
                throw new Exception('Internal server error',500);
            }
             
            $hash         = $this->generate_xh_hash($result,$hashkey);
            if(!isset( $result['hash'])|| $hash!=$result['hash']){
                throw new Exception(__('Invalid sign!',WSHOP),40029);
            }
        
            if($result['errcode']!=0){
                throw new Exception($result['errmsg'],$result['errcode']);
            }
        
            return WShop_Error::success($result['url']);
        } catch (Exception $e) {
           WShop_Log::error($e);
           return WShop_Error::error_custom($e->getMessage());
        }
    }
    
    public function generate_xh_hash(array $datas,$hashkey){
        ksort($datas);
        reset($datas);
         
        $pre =array();
        foreach ($datas as $key => $data){
            if(is_null($data)||$data===''){
                continue;
            }
            if($key=='hash'){
                continue;
            }
            $pre[$key]=$data;
        }
         
        $arg  = '';
        $qty = count($pre);
        $index=0;
         
        foreach ($pre as $key=>$val){
            $arg.="$key=$val";
            if($index++<($qty-1)){
                $arg.="&";
            }
        }
         
        return md5($arg.$hashkey);
    }
    
}
?>