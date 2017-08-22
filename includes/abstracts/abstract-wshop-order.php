<?php
if (! defined('ABSPATH'))
    exit();
 // Exit if accessed directly
 
/**
 * 订单
 * @author rain
 * @since 1.0.0
 */
abstract  class Abstract_WShop_Order extends WShop_Mixed_Object
{
    private $_call_after_insert=array();
    
    public $id;
    
    /**
     * 扩展信息
     * @var array
     */
    public $metas =array();
    /**
     * 订单支付编号
     *
     * @var string
     */
    public $sn;
    
    /**
     * 预支付时，订单商品总额
     *
     * @var float
     */
    public $total_amount = 0;
    
    /**
     * 附加价格：邮费，税率等
     * @var array
     */
    public $extra_amount = array(
    
    );
    
    /**
     * IP
     * @var string
     */
    public $ip;
    
    /**
     * 支付方式ID
     *
     * @var string
     */
    public $payment_method;
    
    /**
     * 下单时间(格林时间)
     *
     * @var int
     */
    public $order_date;
    
    /**
     * 过期时间(格林时间)
     *
     * @var int
     */
    public $expire_date;
    
    /**
     * 已支付时间(格林时间)
     *
     * @var int
     */
    public $paid_date;
    
    /**
     * 第三方支付ID
     *
     * @var string
     */
    public $transaction_id;
    
    /**
     * 当前货币 (CNY,AUD...)
     *
     * @var string
     */
    public $currency;
    
    /**
     * 用户ID
     *
     * @var long|null
     */
    public $customer_id;
    
    /**
     * 订单状态:WP 未支付 ,OD 已支付
     *
     * @var string
     */
    public $status;
    
    public $removed=0;
    
    /**
     * 商品信息
     * @var Abstract_WShop_Order_Item[]
     */
    public $order_items =array();
 
    /**
     * 订单状态：待支付
     *
     * @var string
     */
    const Pending = 'pending';
    
    /**
     * 订单状态：处理中
     *
     * @var string
     */
    const Processing = 'processing';
    /**
     * 订单状态：已完成
     * @var string
     */
    const Complete = 'complete';
    
    /**
     * @param object $wp_order 数据库中查询的data
     */
    public function __construct($wp_order=null)
    {
        parent::__construct($wp_order);
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::is_auto_increment()
     */
    public function is_auto_increment()
    {
        // TODO Auto-generated method stub
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_primary_key()
     */
    public function get_primary_key()
    {
        // TODO Auto-generated method stub
        return 'id';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
        // TODO Auto-generated method stub
        return 'wshop_order';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys(){
        $payment_api = WShop::instance()->payment;
        $order_expire_minute = $payment_api->get_order_expire_minues();
        $now = current_time( 'timestamp');
        
        $expire_date = $order_expire_minute <= 0 ? null : ($now + 60 * $order_expire_minute);
        $currency = $payment_api->get_currency();
        $ip =WShop::instance()->WP->get_client_ip();
        return apply_filters('wshop_order_properties', array(
            'id'=>null,
            //订单支付编号
            'sn'=>null,
            //预支付时，订单商品总额
            'total_amount'=>0,
            'extra_amount'=>array(),
            'class'=>get_called_class(),
            //支付价格
            //支付方式ID
            'payment_method'=>null,
            //下单时间
            'order_date'=>$now,
            //过期时间
            'expire_date'=>$expire_date,
            //已支付时间
            'paid_date'=>null,
            //第三方支付系统ID
            'transaction_id'=>null,
            //货币
            'currency'=>$currency,
            //订单用户ID
            'customer_id'=>null,
            //ip
            'ip'=>$ip,
            //订单状态:WP 未支付 ,OD 已支付
            'status'=>self::Pending,
            'removed'=>0,
            //订单标题
            'metas'=>array()
        ));
    }
    
    /**
     * 插入数据后调用函数库
     * @param function $func
     */
    public function add_call_after_insert($func){
        $params = array($this);
        for($i=1;$i<func_num_args();$i++){
            $params[]=func_get_arg($i);
        }
        
        $this->_call_after_insert[]=array(
            'call'=>$func,
            'params'=>$params
        );
    }

    /**
     * 获取订单状态html
     * @since 1.0.0
     */
    public function get_order_status_html(){
        return "<span class=\"order-status order-status-{$this->status}\">". self::get_status_name($this->status)."</span>";
    }
    /**
     * 获取订单状态名称
     * @since 1.0.0
     */
    public static function get_status_name($status){
        switch ($status){
            case self::Pending:
                return  __('Pending',WSHOP);
            case self::Processing:
                return __('Processing',WSHOP);
            case self::Complete:
                return  __('Complete',WSHOP);
            default:
                return $status;
        }
    }
    
    /**
     * @since 1.0.0
     */
    public function get_title(){
        $title=sprintf(__("#%s",WSHOP),str_pad($this->id,4,'0',STR_PAD_LEFT));
        $items = $this->get_order_items();
        $order_item =$items&&count($items)>0?$items[0]:null;
        if($order_item){
            $title.=" - ".$order_item->get_title();
            if(count($items)>1){
                $title.=' ...';
            }
        }
        
        return apply_filters('wshop_order_title', $title);
    }
    
    /**
     * 已支付成功的订单状态
     * @return array
     * @since 1.0.0
     */
    public static function get_paid_order_status(){
        return array(self::Processing,self::Complete);
    }
    
    /**
     * 获取所有可用的订单干状态
     * @return string[]
     * @since 1.0.0
     */
    public function get_all_order_status(){
        return array(
            self::Processing,
            self::Complete,
            self::Pending
        );
    }
    

    /**
     * 是否已支付
     * @return boolean
     * @since 1.0.0
     */
    public function is_paid(){
        return apply_filters('wshop_order_is_paid', in_array($this->status, array(
            self::Processing,
            self::Complete
        )));
    }
    
    /**
     * 判断是否运行支付
     */
    public function can_pay(){
        if($this->is_paid()){
            return false;
        }
       
        if($this->expire_date){
            return $this->expire_date>current_time( 'timestamp' );
        }
        
        return true;
    }
    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    
    /**
     * 生成订单 插入数据库
     * @param array $request
     * @return WShop_Error|Abstract_WShop_Order
     * @since 1.0.0
     */
    public function create_payment(
        $request,
        $on_pre_order_instert=null,
        $on_after_order_instert=null,
        $on_pre_order_item_instert=null,
        $on_after_order_item_instert=null,
        $on_order_created=null
        ){ 
        $payment_method = isset($_REQUEST['payment_method'])?stripslashes($_REQUEST['payment_method']):null;
       if(!$payment_method){
           return WShop_Error::error_custom(__('Payment gateway is invalid!',WSHOP));
       }
        $cart = new WShop_Shopping_Cart($request['cart_id']);
        if(!$cart->is_load()){
            return WShop_Error::error_custom(__('Shopping cart is invalid!',WSHOP));
        }
        
        $this->payment_method =$payment_method;
        if(is_user_logged_in()){
            $this->customer_id = get_current_user_id();
        }
        
        $this->metas = apply_filters('wshop_order_metas', array(
            'location'=>$request['location']
        ),$request); 
         
        $this->total_amount=0;
        $this->extra_amount=array();
        foreach ($cart->get_items() as $post_id =>$item){
            $product =$item['product'];
            
            $order_item =$product->to_order_item($cart, $request);
            if($order_item instanceof WShop_Error){
                return $order_item;
            }
            
            $this->order_items[]=$order_item;
            $this->total_amount +=$order_item->price*$order_item->qty;
        }
        
        $error = apply_filters('wshop_init_amount_before',WShop_Error::success(), $this,$cart,$request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        $extra_amount_pre = $this->extra_amount;
        $this->extra_amount=array();
       
        $error = apply_filters('wshop_init_amount',WShop_Error::success(),$this,$cart,$request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
       
        $discount =0;
        foreach ($this->extra_amount as $label=>$atts){
            $discount+=$atts['amount'];
        }
        
        $this->total_amount +=$discount;      
        foreach ($extra_amount_pre as $label=>$atts){
            $this->extra_amount[$label] = $atts;
        }
        
        $error = apply_filters('wshop_init_amount_after',WShop_Error::success(),$this,$cart,$request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        $error = apply_filters('wshop_validate_order', WShop_Error::success(),$this,$request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        foreach ($this->order_items as $order_item){
            $error = apply_filters('wshop_validate_order_item', WShop_Error::success(),$order_item, $this,$request);
            if(!WShop_Error::is_valid($error)){
                return $error;
            } 
        }
        
        if($on_pre_order_instert){
            $error = call_user_func_array($on_pre_order_instert,array($this,$request));
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        $error = $this->on_pre_order_instert($request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
    
        $error = $this->insert();
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        foreach ($this->_call_after_insert as $att){
            $func = $att['call'];
            $error = call_user_func_array($func, $att['params']);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        if($on_after_order_instert){
            $error = call_user_func_array($on_after_order_instert,array($this,$request));
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        $error = $this->on_after_order_instert($request);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
    
        //order items
        foreach ($this->order_items as $order_item){
            $order_item->order_id = $this->id;
            
            
            if($on_pre_order_item_instert){
                $error = call_user_func_array($on_pre_order_item_instert,array($order_item,$this,$request));
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
            }
            
            $error = $order_item->on_pre_order_item_instert($this,$request);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            $error = $order_item->insert();
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            if($on_after_order_item_instert){
                $error = call_user_func_array($on_after_order_item_instert,array($order_item,$this,$request));
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
            }
            
            $error = $order_item->on_after_order_item_instert($this,$request);
            
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
    
        if($on_order_created){
            $error = call_user_func_array($on_order_created,array($this,$request));
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        return $this->on_order_created($request);
    }


    /**
     * 获取支付结果页面
     * @return string|NULL
     * @since 1.0.0
     */
    public function get_received_url(){
        return apply_filters( 'wshop_order_received_url', $this->get_review_url(),$this);
    }
    
    /**
     * 订单详情地址
     * @return string
     * @since 1.0.0
     */
    public function get_review_url(){
        $url = WShop::instance()->WP->get_checkout_uri('order-received');
        
        $request= array(
            'order_id'=>$this->id
        );
    
        $request = WShop::instance()->generate_request_params($request,'notice');
    
        $params = array();
        $url = WShop_Helper_Uri::get_uri_without_params($url,$params);
    
        return apply_filters( 'wshop_order_review_url', $url."?".http_build_query(array_merge($request,$params)),$this);
    }
   
    /**
     * 获取支付url
     * @since 1.0.0
     * @return string 
     */
    public function get_pay_url(){
        return WShop::instance()->ajax_url(array(
                    'order_id'=>$this->id,
                    'action'=>'wshop_checkout',
                    'tab'=>'pay'
                ),true,true);
    }
    
    /**
     * 获取支付价格
     * @return float
     * @since 1.0.0
     */
    public function get_total_amount($symbol=false){
        $amount =$this->total_amount;
        
        if($symbol){
            $symbol =WShop_Currency::get_currency_symbol($this->currency);
            $amount = "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($amount);
        }
        
        return $amount;
    }

    /**
     * @return Abstract_WShop_Order_Item[]
     * @since 1.0.0
     */
    public function get_order_items()
    {
        if($this->order_items){
            return $this->order_items;
        }
        
        global $wpdb;
        $order_items =$wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}wshop_order_item where order_id=%s;", $this->id));
      
        if(!$order_items){
            return null;
        }
        
        foreach ($order_items as $order_item){
            $this->order_items[]=WShop_Mixed_Object_Factory::to_entity($order_item);
        }
        
        return $this->order_items;
    }
    
    /**
     * @return Abstract_WShop_Order_Note[]
     * @since 1.0.0
     */
    public function get_order_notes($note_type=null){
        global $wpdb;
        $note_type_sql = empty($note_type)?"":" and note_type='{$note_type}' ";
        $notes = $wpdb->get_results(
            "select *
             from {$wpdb->prefix}wshop_order_note 
             where order_id={$this->id}
                    $note_type_sql
             order by id desc
             limit 40;");
         $results = array();
         if($notes){
             foreach ($notes as $note){
                 $results[]=new WShop_Order_Note($note);
             }
         }
         return $results;
    }
    
    /**
     * 创建支付订单编号
     * @since 1.0.0
     * @return WShop_Error
     */
    public function generate_sn(){
        $prefix = WShop_Settings_Checkout_Options::instance()->get_option('order_prefix');
        if(empty($prefix)){
            $prefix =date_i18n('His')."-";
        }
        
        $sn = apply_filters('wshop_order_generate_sn', $prefix.date_i18n('Ymd').'-'.str_pad($this->id,4,'0',STR_PAD_LEFT));
       
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wshop_order_sn", array(
            'sn'=>$sn,
            'order_id'=>$this->id,
            'created_time'=>current_time( 'timestamp')
        ));
       
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            return WShop_Error::error_custom($wpdb->last_error);
        }
        
        if($wpdb->insert_id<=0){
            return WShop_Error::error_unknow();
        }
        $this->sn  = $sn;
        return $sn;
    }
    
    /**
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_pre_order_instert($request){
       return apply_filters('wshop_order_pre_order_instert', WShop_Error::success(),$this,$request);
    }
    
    /**
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_after_order_instert($request){
       return apply_filters('wshop_order_after_order_instert', WShop_Error::success(),$this,$request);
    }
    
    
    /**
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_order_created($request){
        return apply_filters('wshop_order_order_created', WShop_Error::success(),$this,$request);
    }
    
    /**
     * 获取支付网关
     * @return Abstract_WShop_Payment_Gateway
     * @since 1.0.0
     */
    public function get_payment_gateway(){
        return WShop::instance()->payment->get_payment_gateway($this->payment_method);
    }
    
    public function get_edit_link(){
        return admin_url("admin.php?page=wshop_page_order&section=menu_order_default&tab=menu_order_default_settings&view=edit&id={$this->id}");
    }
    
    /**
     * 更新订单状态
     * @param string $new_status
     * @param array $other_propertys
     * @return WShop_Error
     * @since 1.0.0
     */
    public function change_order_status($new_status,$other_propertys=array()){
        $old_status = $this->status;
        
        if($old_status==$new_status){
            return WShop_Error::success();
        }
        
        $error = apply_filters('wshop_pre_change_order_status', WShop_Error::success(),$new_status,$old_status,$this);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        global $wpdb;
        $request = array_merge(array('status'=>$new_status),$other_propertys);
      
        foreach ($request as $key=>$val){
            $request[$key]= maybe_serialize($val);
        }

        $wpdb->update("{$wpdb->prefix}wshop_order", $request, array(
            'id'=>$this->id
        ));
       
        if(!empty($wpdb->last_error)){
            return WShop_Error::error_custom($wpdb->last_error);
        }
        
        foreach ($request as $key=>$val){
            $this->{$key} = maybe_unserialize($val);
        }
        
        return $this->after_change_order_status($old_status,$new_status);
    }
    
    public function after_change_order_status($old_status,$new_status){
        return apply_filters('wshop_after_change_order_status', $this->add_note(
            WShop_Order_Note::Note_Type_Customer,
            sprintf(__('Order status changed from %s to %s.',WSHOP),self::get_status_name($old_status),self::get_status_name($new_status))
        ),$new_status,$old_status,$this);
    }
    
    public function add_note($note_type,$content){
        global $wpdb;
        $current_user_id = get_current_user_id();
        $note = new WShop_Order_Note(array(
            'order_id'=>$this->id,
            'user_id'=>$current_user_id<=0?null:$current_user_id,
            'created_date'=>current_time( 'timestamp' ),
            'note_type'=>$note_type,
            'content'=>$content
        ));
        
        return $note->insert();
    }
    
    /**
     * 完成支付
     * @since 1.0.0
     * @return WShop_Error
     */
    public function complete_payment($transaction_id=null){
        if(!$this->is_paid()){
           
            $error = $this->on_pre_order_ordered();
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            $status_waitting_payment =self::Pending;
            $status_complete_payment = apply_filters('wshop_order_complete_payment_status', self::Processing);
            global $wpdb;
            
            $sql = apply_filters('wshop_order_complete_payment_sql_1', " update `{$wpdb->prefix}wshop_order` o ",$this);
            
            $request = apply_filters('wshop_order_complete_payment_request', array(
                'status'=>$status_complete_payment,
                'transaction_id'=>$transaction_id,
                'paid_date'=>current_time('timestamp'),
                'sn'=>$this->sn
            ),$this);
            
            $sql.=" set ";
            $index=0;
            foreach ($request as $key=>$val){
                $this->{$key} = $val;
                if($index++!=0){
                    $sql.=",";
                }
                $sql.=" o.{$key}='{$val}' ";
            }
            
            $sql .=apply_filters('wshop_order_complete_payment_sql_2', null,$this)
                 .apply_filters('wshop_order_complete_payment_sql_3', " where o.id='{$this->id}' and o.status='$status_waitting_payment' ",$this);
            
            $result = $wpdb->query($sql);
            if(!empty($wpdb->last_error)){
                WShop_Log::debug($wpdb->last_error);
                return WShop_Error::err_code(500);
            }
            
            //出现连续更新sql的执行，直接退出去，不执行后边的操作了
            if($result){
                //will be call once
                
                $error = $this->after_change_order_status($status_waitting_payment,$status_complete_payment);
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
               
                $error =  $this->on_order_ordered();
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
            }
        }
        
        //will be call more times
        return apply_filters('wshop_complete_payment', WShop_Error::success(),$this);
    }
    
    /**
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_pre_order_ordered(){
        return apply_filters('wshop_order_pre_order_ordered', WShop_Error::success(),$this);
    }
    
    /**
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_order_ordered(){
        return apply_filters('wshop_order_order_ordered', WShop_Error::success(),$this);
    }
    
    public function order_items_view_order_received(){
        echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-items-view-order-received.php',array(
            'order'=>$this
        ));
    }
    
    public function order_items_view_admin_order_list_item(){
       echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-items-view-admin-order-list-item.php',array(
            'order'=>$this
        ));
    }
    
    public function order_view_admin_order_detail(){
       echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-view-admin-order-detail.php',array(
            'order'=>$this
        ));
    }
    
    public function order_items_view_admin_order_detail(){
       echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-items-view-admin-order-detail.php',array(
            'order'=>$this
        ));
    }
   
}


class WShop_Order_Model extends Abstract_WShop_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Model_Api::init()
     */
    public function init()
    {
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query(
        "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_order` (
        	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
        	`class` VARCHAR(64) NOT NULL, 
        	`sn` VARCHAR(64) NULL DEFAULT NULL,
        	`total_amount` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        	`extra_amount` TEXT NULL DEFAULT NULL,
        	`payment_method` VARCHAR(64) NULL DEFAULT NULL,
        	`order_date` INT(11) NOT NULL DEFAULT '0',
        	`expire_date` INT(11) NULL DEFAULT NULL,
        	`paid_date` INT(11) NULL DEFAULT NULL,
        	`transaction_id` VARCHAR(128) NULL DEFAULT NULL,
        	`currency` VARCHAR(6) NOT NULL DEFAULT 'CNY',
        	`customer_id` BIGINT(20) NULL DEFAULT NULL,
        	`status` VARCHAR(16) NOT NULL DEFAULT 'WP',
        	`ip` VARCHAR(16) NULL DEFAULT NULL,
        	`removed` TINYINT(4) NOT NULL DEFAULT '0',
        	`metas` text NULL DEFAULT NULL, 
        	PRIMARY KEY (`id`),
        	UNIQUE INDEX `sn_unique` (`sn`),
        	INDEX `ip_key` (`ip`),
        	INDEX `status_key` (`status`)
        )
        AUTO_INCREMENT=10000
        $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $wpdb->query(
           "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_order_sn` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            	`sn` VARCHAR(64) NOT NULL,
            	`order_id` BIGINT(20) NOT NULL,
            	`created_time` BIGINT(20) NOT NULL,
            	PRIMARY KEY (`id`),
            	UNIQUE INDEX `sn` (`sn`)
            )
            $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_shopping_cart` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            	`customer_id` BIGINT(20) NULL DEFAULT NULL,
            	`items` TEXT NULL,
            	`created_time` BIGINT(20) NULL DEFAULT NULL,
            	`coupons` TEXT NULL DEFAULT NULL,
            	PRIMARY KEY (`id`)
            )
            $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_order_note`(
            	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            	`order_id` BIGINT(20) NOT NULL,
            	`content` TEXT NULL,
            	`created_date` INT(11) NOT NULL,
            	`note_type` varchar(16) NOT NULL,
            	`user_id` INT(11) NULL DEFAULT NULL,
            	PRIMARY KEY (`id`)
            )
            $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}

?>