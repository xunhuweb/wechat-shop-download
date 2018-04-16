<?php
if (! defined('ABSPATH'))
    exit();
 // Exit if accessed directly
 
/**
 * 订单
 * @author rain
 * @since 1.0.0
 */
abstract  class Abstract_WShop_Order extends WShop_Object{
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
     * 订单支付模型
     * @var string
     */
    public $section;
    
    /**
     * 订单类型
     * @var string
     */
    public $obj_type;
    
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
    public $exchange_rate;
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
     * 特殊状态
     * 未确定的订单
     * @var string
     */
    const Unconfirmed ='_';
    
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
     * 已取消
     * @var string
     */
    const Canceled = 'canceled';
    
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
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_primary_key()
     */
    public function get_primary_key()
    {
        return 'id';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
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
            'obj_type'=>null,
            'section'=>null,
           // 'class'=>get_called_class(),
            //支付价格
            //支付方式ID
            'payment_method'=>null,
            //下单时间
            'order_date'=>$now,
            //过期时间
            'expire_date'=>$expire_date,
            'exchange_rate'=>0,
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
            'status'=>self::Unconfirmed,
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

    public function call_after_insert(){
        foreach ($this->_call_after_insert as $att){
            $func = $att['call'];
            $error = call_user_func_array($func, $att['params']);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        return WShop_Error::success();
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
            case self::Canceled:
                return  __('Canceled',WSHOP);
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
     * @return string
     */
    public static function get_waitting_order_status(){
        return self::Pending;
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
     * 未确定的订单
     * @return bool
     */
    public function is_unconfirmed(){
        return apply_filters('wshop_order_is_unconfirmed', in_array($this->status, array(
            self::Unconfirmed
        )));
    }
    
    /**
     * 已取消的订单
     * @return bool
     */
    public function is_canceled(){
        return apply_filters('wshop_order_is_canceled', in_array($this->status, array(
            self::Canceled
        )));
    }
    
    public function is_pending(){
        return apply_filters('wshop_order_is_pending', in_array($this->status, array(
            self::Pending
        )));
    }
    
    public function is_expired(){
        if($this->expire_date){
            return $this->expire_date<=current_time( 'timestamp' );
        }
    
        return false;
    }
    
    /**
     * 判断是否运行支付
     */
    public function can_pay(){
        if($this->is_expired()){
            return false;
        }
        
        return $this->is_pending();
    }
    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
  
    /**
     * 获取支付结果页面
     * @return string|NULL
     * @since 1.0.0
     */
    public function get_received_url(){
        $received_url = $this->get_review_url();
        
        $received_url =  apply_filters( 'wshop_order_received_url',$received_url ,$this);
        $received_url = apply_filters( "wshop_order_{$this->obj_type}_received_url",$received_url,$this);
        $received_url = apply_filters( "wshop_order_{$this->section}_received_url",$received_url,$this);
      
        return $received_url;
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
    
        $review_url = $url."?".http_build_query(array_merge($request,$params));
        
        $review_url = apply_filters( 'wshop_order_review_url',$review_url,$this);
        $review_url = apply_filters( "wshop_order_{$this->obj_type}_review_url",$review_url,$this); 
        $review_url = apply_filters( "wshop_order_{$this->section}_review_url",$review_url,$this);
        return $review_url;
    }
   
    /**
     * 获取支付url
     * @since 1.0.0
     * @return string 
     */
    public function get_pay_url(){
        return WShop::instance()->ajax_url(array(
                    'order_id'=>$this->id,
                    'action'=>'wshop_checkout_v2',
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
            $this->order_items[]=new WShop_Order_Item($order_item);
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
        }else{
            $prefix = rtrim($prefix,'-').'-';
        }
        
        $time = str_replace('.', '', microtime(true));
        $sn = apply_filters('wshop_order_generate_sn', $prefix.$time.'-'.str_pad($this->id,4,'0',STR_PAD_LEFT));
       
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
    public function complete_payment($transaction_id=null,$update_properties = array()){
        if(!$this->is_paid()){
           
            $error = $this->on_pre_order_ordered();
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            $status_waitting_payment = apply_filters('wshop_order_waitting_payment_status',$this->get_waitting_order_status(),$this,$transaction_id);         
            $status_complete_payment = apply_filters('wshop_order_complete_payment_status', self::Processing,$this,$transaction_id);
            $status_complete_payment = apply_filters("wshop_order_{$this->obj_type}_complete_payment_status", $status_complete_payment,$this,$transaction_id);
            $status_complete_payment = apply_filters("wshop_order_{$this->section}_complete_payment_status", $status_complete_payment,$this,$transaction_id);
           
            global $wpdb;
            $defaults = apply_filters('wshop_order_complete_payment_args_default', array_merge(array(
                'status'=>$status_complete_payment,
                'transaction_id'=>$transaction_id,
                'paid_date'=>current_time('timestamp'),
                'sn'=>$this->sn
            ),$update_properties),$this,$transaction_id);
            
            $set="";
            $index = 0;
            foreach ($defaults as $key=>$val){
                if($index++!=0){
                    $set.=",";
                }
                $this->{$key} = $val;
                $set.=$wpdb->prepare("core_wshop_order.{$key}='%s' ", maybe_serialize($val));
            }
            
            $set.=",core_wshop_product.inventory=if(core_wshop_product.inventory is null,null,core_wshop_product.inventory-core_wshop_order_item.inventory)
                   ,core_wshop_product.sale_qty =(core_wshop_product.sale_qty + core_wshop_order_item.inventory)";
            
            $where ="(core_wshop_order.id='{$this->id}' and core_wshop_order.status='$status_waitting_payment') ";
            
            $join = "inner join `{$wpdb->prefix}wshop_order_item` core_wshop_order_item on core_wshop_order_item.order_id = core_wshop_order.id
                    inner join `{$wpdb->prefix}wshop_product` core_wshop_product on core_wshop_product.post_ID = core_wshop_order_item.post_ID ";
            
            $args = apply_filters('wshop_order_complete_payment_args', array(
                'join'=>array(
                    'core'=>$join,
                ),
                'set'=>array(
                    'core'=>$set
                ),
                'where'=>array(
                    'core'=>$where
                ),
                'prepare'=>array(),
                'executed'=>array()
            ),$this,$transaction_id);
         
            $args = apply_filters("wshop_order_{$this->obj_type}_complete_payment_args",$args,$this,$transaction_id);
            $args = apply_filters("wshop_order_{$this->section}_complete_payment_args",$args,$this,$transaction_id);
            
            $sql = "update `{$wpdb->prefix}wshop_order` core_wshop_order";
            foreach ($args['join'] as $key=>$join){
                $sql.=" $join ";
            }
            
            $sql.=" set ";
            foreach ($args['set'] as $key=>$set){
                $sql.=" $set ";
            }
           
            $sql .=" where ";
            foreach ($args['where'] as $key=>$where){
                $sql.=" $where ";
            }
          
            foreach ($args['prepare'] as $key=>$func){
                call_user_func_array($func, array($this,$transaction_id));
            }
         
            $is_update = $wpdb->query($sql);
            if(!empty($wpdb->last_error)){
                WShop_Log::debug($wpdb->last_error.'SQL:'.$sql);
                return WShop_Error::err_code(500);
            }
            //刷新当前订单缓存
            $this->refresh_cache();
            $items = $this->get_order_items();
            if($items)
            foreach ($items as $item){
                clean_post_cache($item->post_ID);
            }
            
            foreach ($args['executed'] as $key=>$funcs){
                if(is_array($funcs)){
                    foreach ($funcs as $func){
                        call_user_func_array($func, array($this,$is_update));
                    }
                }else{
                    call_user_func_array($funcs, array($this,$is_update));
                }
            }
            
            //出现连续更新sql的执行，直接退出去，不执行后边的操作了
            if($is_update){
                //will be call once
                $this->after_change_order_status($status_waitting_payment,$status_complete_payment);
                $this->on_order_ordered();
            }
        }
        
        //will be call more times
        return apply_filters('wshop_complete_payment', WShop_Error::success(),$this);
    }

    /**
     * 释放订单库存
     * @return WShop_Error
     */
    public function free_order($args=array()){
        if(!$this->is_load()){
            throw new Exception(WShop_Error::error_unknow());
        }
        
        $status_waitting_payment = self::Pending;
        global $wpdb;
        $join = "inner join `{$wpdb->prefix}wshop_order_item` core_wshop_order_item on core_wshop_order_item.order_id = core_wshop_order.id
                inner join `{$wpdb->prefix}wshop_product` core_wshop_product on core_wshop_product.post_ID = core_wshop_order_item.post_ID ";
        
        $defaults = apply_filters('wshop_order_free_args_default', array(
            'status'=>self::Canceled
        ));
        
        $set="";
        $index = 0;
        foreach ($defaults as $key=>$val){
            if($index++!=0){
                $set.=",";
            }
            $this->{$key} = $val;
            $set.=$wpdb->prepare("core_wshop_order.{$key}='%s' ", maybe_serialize($val));
        }
        
        $now = current_time( 'timestamp' );
        $where ="(  core_wshop_order.id='{$this->id}'
                    and core_wshop_order.expire_date is not null
                    and core_wshop_order.expire_date<$now
                    and core_wshop_order.status='$status_waitting_payment'
                 ) ";
        
        if(!is_array($args)){
            $args=array();
        }
        
        if(!isset($args['join'])||!is_array($args['join'])){
            $args['join']=array();
        }
        
        if(!isset($args['set'])||!is_array($args['set'])){
            $args['set']=array();
        }
        
        if(!isset($args['where'])||!is_array($args['where'])){
            $args['where']=array();
        }
        
        if(!isset($args['prepare'])||!is_array($args['prepare'])){
            $args['prepare']=array();
        }
        
        if(!isset($args['executed'])||!is_array($args['executed'])){
            $args['executed']=array();
        }
        
        global $wpdb;
        $args = apply_filters('wshop_order_free_args', array(
            'join'=>array_merge(array(
                'core'=>$join
             ),$args['join']),
            'set'=>array_merge(array(
                'core'=>$set
            ),$args['set']),
            'where'=>array_merge(array(
                'core'=>$where
            ),$args['where']),
            'prepare'=>$args['prepare'],
            'executed'=>$args['executed']
        ));
        
        $sql = "update `{$wpdb->prefix}wshop_order` core_wshop_order";
        foreach ($args['join'] as $key=>$join){
            $sql.=" $join ";
        }
        
        $sql.=" set ";
        foreach ($args['set'] as $key=>$set){
            $sql.=" $set ";
        }
        
        $sql.=" where ";
        foreach ($args['where'] as $key=>$where){
            $sql.=" $where ";
        }
        
        foreach ($args['prepare'] as $key=>$func){
            call_user_func_array($func,array($this));
        }
        
        $result = $wpdb->query($sql); 
        if(!empty($wpdb->last_error)){
            WShop_Log::debug($wpdb->last_error);
            return WShop_Error::err_code(500);
        }
        
        $this->refresh_cache();
        $items = $this->get_order_items();
        if($items)
        foreach ($items as $item){
            clean_post_cache($item->post_ID);
        }
        
        foreach ($args['executed'] as $key=>$funcs){
            if(is_array($funcs)){
                foreach ($funcs as $func){
                    call_user_func_array($func, array($this,$result));
                }
            }else{
                call_user_func_array($funcs, array($this,$result));
            }
        }
        
        return WShop_Error::success();
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
    
    public function order_view_title_order_received(){
        echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-view-title-order-received.php',array(
            'order'=>$this
        ));
    }
    
    public function order_view_desc_order_received(){
        echo WShop::instance()->WP->requires(WSHOP_DIR, 'order/order-view-desc-order-received.php',array(
            'order'=>$this
        ));
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
   
    /**
     * 获取邮件接收者
     * @since 1.0.2
     */
    public function get_email_receiver(){
        $email = null;
        $user=null;
        if($this->customer_id){
            $user = get_userdata($this->customer_id);
            if($user&&!empty($user->user_email)){
                $email=$user->user_email;
            }
        }
        
        $email = apply_filters('wshop_order_email_receiver', $email,$this);
        $email = apply_filters("wshop_order_{$this->obj_type}_email_receiver", $email,$this);
        
        return $email;
    }
    
}

//TODO 此类已作废
// class WShop_Order_Session extends WShop_Object{
//     public $session_id;
//     public $order_id;
//     public $metas=array();
    
//     /**
//      * {@inheritDoc}
//      * @see WShop_Object::is_auto_increment()
//      */
//     public function is_auto_increment()
//     {
//         return false;
//     }

//     /**
//      * {@inheritDoc}
//      * @see WShop_Object::get_primary_key()
//      */
//     public function get_primary_key()
//     {
//         return 'session_id';
//     }

//     /**
//      * {@inheritDoc}
//      * @see WShop_Object::get_table_name()
//      */
//     public function get_table_name()
//     {
//         return 'wshop_order_session';
//     }

//     /**
//      * {@inheritDoc}
//      * @see WShop_Object::get_propertys()
//      */
//     public function get_propertys()
//     {
//         return array(
//             'session_id'=>null,
//             'order_id'=>null,
//             'metas'=>array()
//         );
//     }
// }

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
        	`obj_type` VARCHAR(64) NULL DEFAULT NULL, 
        	`section` VARCHAR(64) NULL DEFAULT NULL, 
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
        	`exchange_rate` DECIMAL(18,4) NOT NULL DEFAULT '0.0000',
        	`metas` text NULL DEFAULT NULL, 
        	PRIMARY KEY (`id`),
        	UNIQUE INDEX `sn_unique` (`sn`),
        	INDEX `ip_key` (`ip`),
        	INDEX `transaction_id_key` (`transaction_id`),
        	INDEX `status_key` (`status`)
        )
        AUTO_INCREMENT=10000
        $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
           "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_order'
            and table_schema ='".DB_NAME."'
					and column_name ='class'
			limit 1;");
        
        if($column&&!empty($column->column_name)){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}wshop_order` DROP COLUMN `class`;");
        }
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
           "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_order'
                    and table_schema ='".DB_NAME."'
					and column_name ='exchange_rate'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_order` add column `exchange_rate` DECIMAL(18,4) NOT NULL DEFAULT '0.0000';");
        }
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
            "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_order'
                  and table_schema ='".DB_NAME."'
				  and column_name ='obj_type'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_order` add column `obj_type` VARCHAR(64) NULL DEFAULT NULL;");
        }
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
            "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_order'
            and table_schema ='".DB_NAME."'
				  and column_name ='section'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_order` add column `section` VARCHAR(64) NULL DEFAULT NULL;");
        }
        
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
        
//         $wpdb->query(
//             "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_order_session` (
//                 `session_id` VARCHAR(32) NOT NULL,
//                 `order_id` BIGINT(20) NULL DEFAULT NULL,
//                 `metas` text NULL DEFAULT NULL, 
//                 PRIMARY KEY (`session_id`)
//              )
//             $collate;");
        
//         if(!empty($wpdb->last_error)){
//             WShop_Log::error($wpdb->last_error);
//             throw new Exception($wpdb->last_error);
//         }
        
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_shopping_carts` (
            	`customer_id` varchar(32) NOT NULL,
            	`obj_type` VARCHAR(64) NULL DEFAULT NULL, 
            	`order_id` BIGINT(20) NULL DEFAULT NULL,
            	`items` TEXT NULL,
            	`payment_method` varchar(64) NULL DEFAULT NULL,
            	`created_time` BIGINT(20) NULL DEFAULT NULL,
            	`coupons` TEXT NULL DEFAULT NULL,
            	`metas` text NULL DEFAULT NULL, 
            	PRIMARY KEY (`customer_id`)
            )
            $collate;");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        $column =$wpdb->get_row(
            "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_shopping_carts'
            and table_schema ='".DB_NAME."'
					and column_name ='obj_type'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_shopping_carts` add column `obj_type` VARCHAR(64) NULL DEFAULT NULL;");
        }
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
           "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_shopping_carts'
            and table_schema ='".DB_NAME."'
					and column_name ='order_id'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_shopping_carts` add column `order_id` BIGINT(20) NULL DEFAULT NULL;");
        }
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
            "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_shopping_carts'
            and table_schema ='".DB_NAME."'
					and column_name ='payment_method'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_shopping_carts` add column `payment_method` varchar(64) NULL DEFAULT NULL;");
        }
        
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