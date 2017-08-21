<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

class WShop_Payment_Gateway_Wpopen_Alipay extends Abstract_WShop_Payment_Gateway{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Payment_Gateway_Alipay
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return WShop_Payment_Gateway_Wpopen_Alipay
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='wpopen_alipay';
        $this->title=__('Alipay',WSHOP);
        $this->description ='当前支付插件专为个人用户使用，如果您是企业用户，请使用 <a href="https://www.wpweixin.net" target="_blank">企业版插件</a>';
        $this->icon=WSHOP_URL.'/assets/image/alipay-l.png';
        $this->icon_small=WSHOP_URL.'/assets/image/alipay.png';
        
        $this->init_form_fields ();
        $this->enabled ='yes'==$this->get_option('enabled');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Abstract_WShop_Settings::init_form_fields()
     */
    public function init_form_fields() {
        $appid ='20146122711';
        $appsecret ='44E76C565F233E4CBB4F5E1B26E2D2A1';
        $this->form_fields = array (
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', WSHOP ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable alipay payment', WSHOP ),
                'default' => 'no'
            ),
            'appid' => array (
                'title' => __ ( 'APP ID', WSHOP ),
                'type' => 'text',
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
            )
        );
        
        $new_id = $this->get_option('appid');
        if($appid==$new_id){
            $this->form_fields['appid']['description']='(此账户为测试支付账号，不退款)';
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_WShop_Payment_Gateway::process_payment()
     */
    public function process_payment($order)
    {
        //创建订单支付编号
        $sn = $order->generate_sn();
        if($sn instanceof WShop_Error){
           return $sn;
        }
        
        $data=array(
            'version'   => '1.1',//api version
            'lang'       => get_option('WPLANG','zh-cn'),
            'is_app'    => WShop_Helper_Uri::is_app_client()?'Y':'N',
            'plugins'   => 'wshop-alipay',
            'appid'     => $this->get_option('appid'),
            'trade_order_id'=> $sn,
            'payment'   => 'alipay',
            'total_fee' => $order->get_total_amount(false),
            'title'     => $order->get_title(),
            'description'=> null,
            'time'      => time(),
            'notify_url'=> home_url('/'),
            'return_url'=> $order->get_received_url(),
            'callback_url'=>home_url('/'),
            'nonce_str' => str_shuffle(time())
        );
        
        $hashkey          = $this->get_option('appsecret');
        $data['hash']     = $this->generate_xh_hash($data,$hashkey);
        $url              = 'https://pay.wordpressopen.com/payment/do.html';
        
        try {
            $response     = WShop_Helper_Http::http_post($url, json_encode($data));
            $result       = $response?json_decode($response,true):null;
            if(!$result){
                throw new Exception('Internal server error',500);
            }
             
            $hash         = $this->generate_xh_hash($result,$hashkey);
            if(!isset( $result['hash'])|| $hash!=$result['hash']){
                throw new Exception(__('Invalid sign!',XH_Alipay_Payment),40029);
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