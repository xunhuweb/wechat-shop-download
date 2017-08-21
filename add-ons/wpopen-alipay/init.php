<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

require_once 'class-wshop-payment-gateway-alipay.php';	   
/**
 * @author rain
 *
 */
class WShop_Add_On_Wpopen_Alipay extends Abstract_WShop_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Add_On_Alipay
     */
    private static $_instance = null;

    /**
     * 插件跟路径url
     * @var string
     * @since 1.0.0
     */
    public $domain_url;
    public $domain_dir;
    
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return WShop_Add_On_Alipay
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='wshop_add_ons_wpopen_alipay';
        $this->title=__('Alipay(WP OPEN)',WSHOP);
        $this->description='个人免签约支付宝支付网关';
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',WSHOP);
        $this->author_uri='https://www.wpweixin.net';
        $this->domain_url = WShop_Helper_Uri::wp_url(__FILE__) ;
        $this->domain_dir = WShop_Helper_Uri::wp_dir(__FILE__) ;
        $this->setting_uris=array(
            'settings'=>array(
                'title'=>__('Settings',WSHOP),
                'url'=>admin_url("admin.php?page=wshop_page_default&section=menu_default_checkout&sub=wpopen_alipay")
            )
        );
    }

    public function on_load(){
        add_action('wshop_after_init', array($this,'on_alipay_paid_callback'),999);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_init()
     */
    public function on_init(){
        add_filter('wshop_admin_menu_menu_default_checkout', function($menu){
            $menu[]= WShop_Payment_Gateway_Wpopen_Alipay::instance();
            return $menu;
        },10,1);
        
        add_filter('wshop_payments', function($payment_gateways){
            $payment_gateways[] =WShop_Payment_Gateway_Wpopen_Alipay::instance();
            return $payment_gateways;
        },10,1);
    }

    /**
     * 监听支付成功回调
     * @since 1.0.0
     */
    public function on_alipay_paid_callback(){ 
        $data = $_POST;
        if(!isset($data['hash'])||!isset($data['trade_order_id'])){
            return;
        }
        
        if(!isset($data['plugins'])||$data['plugins']!='wshop-alipay'){
            return;
        }
        
        $api =WShop_Payment_Gateway_Wpopen_Alipay::instance();
        $appkey =$api->get_option('appsecret');
        $hash =$api->generate_xh_hash($data,$appkey);
        if($data['hash']!=$hash){
            return;
        }
        
        if($data['status']=='OD'){
            $order = WShop::instance()->payment->get_order('sn', $data['trade_order_id']);
            if(!$order){
                WShop_Log::error('invalid order:'.print_r($data,true));
                echo 'faild';
                exit;
            }
    
            $error =$order->complete_payment($data['transacton_id']);
            if(!WShop_Error::is_valid($error)){
                WShop_Log::error('complete_payment fail:'.$error->errmsg);
                echo 'faild!';
                exit;
            }
        }
        
        $params = array(
            'action'=>'success',
            'appid'=>$api->get_option('appid')
        );
        
        $params['hash']=$api->generate_xh_hash($params, $appkey);
        ob_clean();
        print json_encode($params);
        exit;
    }
   
}

return WShop_Add_On_Wpopen_Alipay::instance();
?>