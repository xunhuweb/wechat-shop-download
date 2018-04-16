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
    public function is_validate_get_pay_per_view($post_id,$roles=array()){
        if(!$post_id){
            return false;
        }
        
        if(WShop::instance()->payment->is_user_roles_allowed($roles)){
            return true;
        }
        
        global $wpdb;
        $status_sql ="'".join("','", Abstract_WShop_Order::get_paid_order_status())."'";
        $user_id = get_current_user_id();
        
        if(WShop::instance()->WP->is_enable_guest_purchase()){
            $ip = WShop_Helper_Http::get_client_ip();
            $now = time()-60*60;
            $order = $wpdb->get_row($wpdb->prepare(
               "select o.id
                from {$wpdb->prefix}wshop_order_item oi
                inner join {$wpdb->prefix}wshop_order o on o.id = oi.order_id
                where oi.post_ID={$post_id}
                    and o.status in ($status_sql)
                    and o.removed=0
                    and o.paid_date>$now
                    and (o.customer_id=$user_id or o.ip='%s')
                order by o.order_date desc
                limit 1;", $ip));
        }else{
            if(!is_user_logged_in()){
                return false;
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
        }
        
    
        return $order?true:false;
    }
    public function get_exchange_rate(){
        $exchange_rate =  round(floatval(WShop_Settings_Default_Basic_Default::instance()->get_option('exchange_rate')),3);
        if($exchange_rate<=0){$exchange_rate=1;}
        return $exchange_rate;
    }
    
    private function remove_role_empty_valus($roles=array()){
        if(!$roles||!is_array($roles)){
            $roles = array();
        }
        
        $_roles = array();
        foreach ($roles as $role){
            if(!empty($role)){
                $_roles[]=$role;
            }
        }
        
        return $_roles;
    }
    public function is_user_roles_allowed($roles=array()){
        global $current_user;
        if(!is_user_logged_in()){
            return false;
        }
        
        if(!$current_user->roles||!is_array($current_user->roles)){
            $current_user->roles=array();
        }
 
        $roles = $this->remove_role_empty_valus($roles);
       
        if(count($roles)==0){
            $roles =$this->remove_role_empty_valus(apply_filters('wshop_unlimit_roles', array()));
        }
     
        //所有会员必须在线下单
        if(in_array('none', $roles)){
            return false;
        }
        
        //所有会员都可查看
        if(in_array('all', $roles)){
            return true;
        }
        
        foreach ($current_user->roles as $role){
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
    
        return new WShop_Order($wp_order);
    }
  
    /**
     * 获取支持在线支付的post type
     * @return string[]
     * @since 1.0.0
     */
    public function get_online_post_types(){
        global $wp_post_types;
        $types = array();
        
        if($wp_post_types){
            foreach ($wp_post_types as $key=>$type){
                if(isset($type->wshop_include)&&$type->wshop_include){
                    $types[]=$key;
                }
            }
        }
        
        $post_types= apply_filters('wshop_online_post_types',$types);
        
        $_results = array();
        foreach ($post_types as $type){
            $_results[$type] =  isset($wp_post_types[$type])&&isset($wp_post_types[$type]->label)&&!empty($wp_post_types[$type]->label)?"{$wp_post_types[$type]->label}({$type})":$type;
        }
   
        return $_results;
    }
  
 
    /**
     * 获取当前货币
     * @return string
     * @since 1.0.0
     */
    public function get_currency(){
        $currency =  WShop_Settings_Default_Basic_Default::instance()->get_option('currency');
        if(empty($currency)){$currency='CNY';}
        return $currency;
    }
    
    /**
     * 获取订单过期时间
     * @return int
     * @since 1.0.0
     */
    public function get_order_expire_minues(){
        return intval(WShop_Settings_Checkout_Options::instance()->get_option('order_expire_minute'));
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
        if(!$id){
            return null;
        }
        return WShop_Helper_Array::first_or_default($this->get_payment_gateways(),function($m,$id){
            return $m->id===$id;
        },$id);
    }
}