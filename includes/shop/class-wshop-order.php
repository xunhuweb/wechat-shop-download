<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

class WShop_Order extends Abstract_WShop_Order{
    
    
    /**
     * @param object $wp_order
     */
    public function __construct($wp_order=null){
        parent::__construct($wp_order);
    }
    
    public static function free_orders($limit = 30){
        global $wpdb;
        $now = current_time( 'timestamp' );
        $status_waitting_payment = self::Pending;
        
        $expired_orders = $wpdb->get_results(
            "select *
             from {$wpdb->prefix}wshop_order 
             where expire_date is not null
                   and expire_date<$now
                   and status = '$status_waitting_payment'
             limit $limit;");
        
        if($expired_orders){
            foreach ($expired_orders as $order_entity){
                $order = new WShop_Order($order_entity);
                if($order->is_load()){
                    $order->free_order();
                }
            }
        }
    }
    
    public function __set_metas($metas =array()){
        if(!is_array($metas)){$metas=array();}
        
        return $this->set_change('metas', array_merge($this->metas,$metas));
    }
}

class WShop_Order_Item extends Abstract_WShop_Order_Item{
    /**
     * @param WShop_Product $product
     */
    public function __construct($wp_order_item=null){
        parent::__construct($wp_order_item); 
    }
}


class WShop_Order_Note extends WShop_Object{
    const Note_Type_Customer='customer';
    const Note_Type_Private='private';
    /**
     * @since 1.0.0
     * @return string[]
     */
    public static function get_note_types(){
        return array(
            self::Note_Type_Private=>__('Private remark',WSHOP),
            self::Note_Type_Customer=>__('Remark for customer',WSHOP)
        );
    }

    public function __construct($wp=null)
    {
        parent::__construct($wp);
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
        return 'wshop_order_note';
    }

    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    {
        // TODO Auto-generated method stub
        return array(
            'id'=>0,
            'order_id'=>null,
            'content'=>null,
            'created_date'=>current_time( 'timestamp' ),
            'note_type'=>self::Note_Type_Private,
            'user_id'=>null
        );
    }
}



class WShop_Order_Helper{
     
    /**
     * 更新订单状态
     * @param WShop_Order $order
     * @param string $new_status
     * @param array $other_propertys
     * @return WShop_Error
     * @since 1.0.0
     */
    private static function change_order_status($order,$new_status,$other_propertys=array()){
        $old_status = $order->status;
    
        if($old_status==$new_status){
            return WShop_Error::success();
        }
    
        global $wpdb;
        $request = array_merge(array('status'=>$new_status),$other_propertys);
    
        foreach ($request as $key=>$val){
            $request[$key]= maybe_serialize($val);
        }
    
        $wpdb->update("{$wpdb->prefix}wshop_order", $request, array(
            'id'=>$order->id
        ));
         
        if(!empty($wpdb->last_error)){
            return WShop_Error::error_custom($wpdb->last_error);
        }
    
        foreach ($request as $key=>$val){
            $order->{$key} = maybe_unserialize($val);
        }
    
        return $order->after_change_order_status($old_status,$new_status);
    }
    
    public static function update_order($order_id,$action){
        $order = WShop::instance()->payment->get_order('id',$order_id);
        if(!$order){
            return WShop_Error::err_code(404);
        }
         
        try {
            global $wpdb;
            switch ($action){
                case 'restore':
                    $wpdb->update("{$wpdb->prefix}wshop_order", array(
                        'removed'=>0
                    ),array(
                        'id'=>$order->id
                    ));

                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'delete':
                    $wpdb->delete("{$wpdb->prefix}wshop_order",array(
                        'id'=>$order->id
                    ));

                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'trash':
                    $wpdb->update("{$wpdb->prefix}wshop_order", array(
                        'removed'=>1
                    ),array(
                        'id'=>$order->id
                    ));

                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'mark_processing':
                    $error =self::change_order_status($order,Abstract_WShop_Order::Processing);
                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'mark_canceled':
                    $error =self::change_order_status($order,Abstract_WShop_Order::Canceled);
                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'mark_complete':
                    $error =self::change_order_status($order,Abstract_WShop_Order::Complete);
                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
                case 'mark_pending':
                    $error =self::change_order_status($order,Abstract_WShop_Order::Pending);
                    if(!empty($wpdb->last_error)){
                        throw new Exception($wpdb->last_error);
                    }
                    break;
            }
        } catch (Exception $e) {
            return WShop_Error::error_custom($e->getMessage());
        }
         
        return WShop_Error::success();
    }
}
?>