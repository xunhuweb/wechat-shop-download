<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 登录apis
 * 
 * @author rain
 * @since 1.0.0
 */
class WShop_Payment_Api{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
    * Main Social Instance.
    *
    * Ensures only one instance of Social is loaded or can be loaded.
    *
    * @since 1.0.0
    * @static
    * @return WShop_Payment_Api - Main instance.
    */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        
    }
    public function is_validate_get_pay_per_view($post_id,$roles){
        if(!$post_id){
            return false;
        }
    
        global $wpdb;
        $status_sql ="'".join("','", Abstract_WShop_Order::get_paid_order_status())."'";
        $user_id = get_current_user_id();
        if($user_id<=0){
            return false;
        }
        if(WShop::instance()->payment->is_user_roles_allowed($roles)){
            return true;
        }
    
        $order = $wpdb->get_row(
            "select o.id
            from {$wpdb->prefix}wshop_order_item oi
            inner join {$wpdb->prefix}wshop_order o on o.id = oi.order_id
            where oi.post_ID={$post_id}
                    and o.status in ($status_sql)
                    and o.removed=0
                    and o.customer_id=$user_id
            order by o.order_date desc
            limit 1;");
    
        return $order?true:false;
    }
    
    public function is_user_roles_allowed($roles){
        if(!$roles||!is_array($roles)){
            $roles =array();
        }
        
        global $current_user;
        if(!is_user_logged_in()){
            return false;
        }
        
        if(!$current_user->roles||!is_array($current_user->roles)){
            $current_user->roles=array();
        }
        
        $unlimit_roles = apply_filters('wshop_membership_download_unlimit_roles', array('administrator'));
        foreach ($current_user->roles as $role){
            if(in_array($role, $unlimit_roles)){
                return true;
            }
        
            if(in_array($role, $roles)){
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 获取当前页面的商品
     * @param int $post_ID
     * @since 1.0.0
     */
    public function get_current_product($post_ID=null){
        if(!$post_ID){
            global $wp_query;
            $default_post=$wp_query->post;
            return new WShop_Product($default_post);
        }
    
        return new WShop_Product(get_post($post_ID));
    }
   
    public function add_to_cart($post_id){
        $product = new WShop_Product($post_id);
        if(!$product->is_load()){
            return WShop_Error::error_custom(__('Product info is invalid!',WSHOP));
        }
        $shopping_cart = new WShop_Shopping_Cart(array(
            'customer_id'=>get_current_user_id(),
            'items'=> array(
                $post_id=>array(
                    'qty'=>1
                )
            ),
            'created_time'=>current_time( 'timestamp' )
        ));
    
        $error = $shopping_cart->insert();
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
         
        return $shopping_cart;
    }
    public  function pay($request){
        $order = apply_filters('wshop_pay_new_order', new WShop_Order());
    
        /*
         $on_pre_order_instert=null,
         $on_after_order_instert=null,
         $on_pre_order_item_instert=null,
         $on_after_order_item_instert=null,
         $on_order_created=null
         * */
        $error =  $order->create_payment(
            $request,
            function($order,$request){
                return apply_filters('wshop_pay_on_pre_order_instert',WShop_Error::success(), $order,$request);
            },
            function($order,$request){
                return apply_filters('wshop_pay_on_after_order_instert',WShop_Error::success(), $order,$request);
            },
            function($order_item,$order,$request){
                return apply_filters('wshop_pay_on_pre_order_item_instert',WShop_Error::success(), $order_item,$order,$request);
            },
            function($order_item,$order,$request){
                return apply_filters('wshop_pay_on_after_order_item_instert',WShop_Error::success(), $order_item,$order,$request);
            },
            function($order,$request){
                return apply_filters('wshop_pay_on_order_created',WShop_Error::success(), $order,$request);
            });
         
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
         
        return $order;
    }
    public function pay_atts(){
        $request = array(
            'location'=>isset($_GET['location'])?esc_url_raw($_GET['location']):null,
            'context'=>strtolower(WShop_Helper_String::guid()),
            'enable_guest'=>WShop_Settings_Checkout_Options::instance()->get_option('enable_guest_checkout','yes')==='yes'?1:0,
        );
        
        return apply_filters('wshop_pay_atts',$request);
    }
  
    /**
     * 获取购物车页面
     * @return NULL|WP_Post
     * @since 1.0.0
     */
    public function get_page_shopping_cart(){
        ;
        return ;
    }
  
    /**
     * 获取购物车 链接
     * @param array $request
     * @return NULL|string
     *  @since 1.0.0
     */
    public function get_order_checkout_url(){
        $url = WShop::instance()->WP->get_checkout_uri('order-pay');
    	return apply_filters( 'wshop_checkout_pay_uri', $url, $this );
    }
    
    /**
     * 获取订单信息
     * @param string $field order_id|sn...
     * @param mixed $field_val
     * @return NULL|Abstract_WShop_Order
     * @since 1.0.0
     */
    public function get_order($field,$field_val){
        if(empty($field)||!is_string($field)){
            throw new Exception('Invalid param field!');
        }
        if(empty($field_val)){
            return null;
        }
        
        global $wpdb;
        $wp_order=null;
        if($field==='sn'){
            $wp_order = $wpdb->get_row($wpdb->prepare(
               "select o.* 
                from `{$wpdb->prefix}wshop_order` o
                inner join `{$wpdb->prefix}wshop_order_sn` sn on sn.order_id = o.id
                where sn.sn=%s 
                limit 1;", $field_val));
            //这段代码不能移除，否则订单更新时，找不到sn
            $wp_order->sn = $field_val;
        }else{
            $wp_order = $wpdb->get_row($wpdb->prepare(
               "select * 
                from `{$wpdb->prefix}wshop_order` 
                where $field=%s 
                limit 1;", $field_val));
        }
        
        
        if(!$wp_order){
            return null;
        }
    
        return WShop_Mixed_Object_Factory::to_entity($wp_order);
    }
  
    /**
     * 获取支持在线支付的post type
     * @return string[]
     * @since 1.0.0
     */
    public function get_online_post_types(){
        static $wshop_online_post_types=false;
        if($wshop_online_post_types!==false){  
            return $wshop_online_post_types;
        }
        
        $post_types= apply_filters('wshop_online_post_types', array());
        if(!did_action('init')){
            throw new Exception('get_online_post_types can be visit after init action');
        }
        
        global $wp_post_types;
        $wshop_online_post_types = array();
        if($post_types&&$wp_post_types){
            foreach ($wp_post_types as $key=>$type){
                if(!in_array($key, $post_types)){continue;}
               
                if($type->show_ui&&$type->public){
                    $wshop_online_post_types[$type->name]=(empty($type->label)?$type->name:$type->label).'('.$type->name.')';
                }
            }
        }
   
        return $wshop_online_post_types;
    }
  
 
    /**
     * 获取当前货币
     * @return string
     * @since 1.0.0
     */
    public function get_currency(){
        return WShop_Settings_Default_Basic_Default::instance()->get_option('currency');
    }
    
    /**
     * 获取订单过期时间
     * @return int
     * @since 1.0.0
     */
    public function get_order_expire_minues(){
        return intval(WShop_Settings_Default_Basic_Default::instance()->get_option('order_expire_minute'));
    }
    /**
     * 获取所有登录接口(已开启的)
     * @param array $action_includes 接口约束
     * @return Abstract_WShop_Payment_Gateway[]
     * @since 1.0.0
     */
    public function get_payment_gateways(){
        $payments = apply_filters('wshop_payments', array());
        
        $results = array();
        foreach ($payments as $payment){ 
            if(!$payment
                ||!$payment instanceof Abstract_WShop_Payment_Gateway
                ||!$payment->is_available()){
                continue;
            }
            
            $results[]=$payment;
        }
      
        return $results;
    }
   
    /**
     * 获取return 页面
     * @param WShop_Order $order
     * @return NULL|string
     * @since 1.0.0
     */
    public function get_order_received_view($order){
        return WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-received.php',array(
            'order'=>$order
        ));
    }
    
    /**
     * 获取登录接口(已开启的)
     * @param string $id
     * @param array $action_includes 接口约束 
     * @return Abstract_WShop_Payment_Gateway
     */
    public function get_payment_gateway($id){   
        return WShop_Helper_Array::first_or_default($this->get_payment_gateways(),function($m,$id){
            return $m->id===$id;
        },$id);
    }
}