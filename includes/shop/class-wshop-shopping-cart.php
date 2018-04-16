<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

class WShop_Shopping_Cart extends Abstract_WShop_Shopping_Cart{
    public function __construct($wp_cart=null){
        parent::__construct($wp_cart);
    }
    
    /**
     * @return WShop_Shopping_Cart
     */
    public static function get_cart(){
       $cart =  WShop_Temp_Helper::get('cart','object',null);
       if($cart){
           $cart->__free_order_hook();
           return $cart;
       }
       
        $customer_id = WShop::instance()->session->get_customer_id();
        
        $cart =  new WShop_Shopping_Cart($customer_id);
        if(!$cart->is_load()){
            $cart->created_time = current_time( 'timestamp' );
            $cart->customer_id = $customer_id;
            $error = $cart->insert();
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }else{
            $cart->__free_order_hook();
        }
        
        WShop_Temp_Helper::set('cart', $cart,'object');
        return $cart;
    }
    
    public function get_payment_method(){
        return $this->get('payment_method');
    }
    
    public function get_total_qty(){
        $items = $this->get_items();
        if($items instanceof WShop_Error){
            self::empty_cart();
            return 0;
        }
        
        $total = 0;
        if($items){
            foreach ($items as $post_ID=>$settings){
                $total+=isset($settings['qty'])?absint($settings['qty']):0;
            }
        }
        
        return $total;
    }
    
    /**
     * @return WShop_Shopping_Cart
     */
    public function __empty_cart(){
       return $this->set_change('coupons', array())
        ->set_change('items', array())
        ->set_change('order_id', null)
        ->set_change('metas', array())
        ->set_change('obj_type', null)
        ->set_change('payment_method', null);
    }
    
    /**
     * 释放购物车
     */
    public function __free_order_hook(){
        $order_id = $this->order_id;
        if(!$order_id){
            return;
        }
        
        $order = new WShop_Order($order_id);
        if(!$order->is_load()){
            $this->__empty_cart()->save_changes();
            return;
        }
        
        if($order->is_paid()){
            $this->__empty_cart()->save_changes();
            return;
        }
    }
    
    public function set_order($order_id){
        $this->__set_order($order_id);
        
        return $this->save_changes();
    }
    
    public function __set_order($order_id){
        return $this->set_change('order_id', $order_id);
    }
    
    public static function add_to_cart($post_id,$qty =1,$inventory_valid_func=null,$metas=array()){
        $cart = WShop_Shopping_Cart::get_cart();
        if($cart instanceof WShop_Error){
            return $cart;
        }
        try {
            $cart->__add_to_cart($post_id,$qty,$inventory_valid_func,$metas);
        } catch (Exception $e) {
            $code =$e->getCode();
            return new WShop_Error($code==0?-1:$code,$e->getMessage());
        }
        return $cart->save_changes();
    }
    
    public function __set_payment_method($payment_method_id){
        if($payment_method_id==null){
            return $this->set_change('payment_method', null);
        }
        
        $payment_method = WShop::instance()->payment->get_payment_gateway($payment_method_id);
        if(!$payment_method){
            throw new Exception(sprintf(__('unknow payment method:%s',WSHOP),$payment_method_id));
        }
        
        return $this->set_change('payment_method', $payment_method->id);
    }
    
    public function __set_metas($metas =array()){
        if(!is_array($metas)){$metas=array();}
        
        return $this->set_change('metas', array_merge($this->metas,$metas));
    }
    
    public function create_order($section,$on_order_created=null,$on_order_item_created=null,$status_of_order=null){
        $order = new WShop_Order();

        $order->payment_method =$this->get_payment_method();
        //如果支付方式未选择，那么订单为未确定订单，不进入订单列表
        $order->status =$status_of_order?$status_of_order: WShop_Order::Unconfirmed;
        
        if(is_user_logged_in()){
            $order->customer_id = get_current_user_id();
        }
      
        $order->section = $section;
        $order->metas = $this->metas;
        $order->obj_type = $this->obj_type;
        $order->exchange_rate =  round(floatval(WShop_Settings_Default_Basic_Default::instance()->get_option('exchange_rate')),4);
        $order->total_amount=0;
        
        $items =$this->get_items(true);
        if($items instanceof WShop_Error){
            return $items;
        }
        $post_type=null;
        foreach ($items as $post_id =>$item){
            $product =$item['product'];
            if(!$product
                ||!$product instanceof WShop_Product
                ||
                (
                    !is_null($post_type)
                    &&$product->post->post_type!=$post_type)
                ){
                continue;
            }
    
            $post_type=$product->post->post_type;
            $order_item_creator = apply_filters('wshop_order_item_creator', function($order,$item){
                $product =$item['product'];
                $order_item =new WShop_Order_Item();
                $order_item->price = $product->get_single_price(false);
                $order_item->qty =$item['qty'];
                $order_item->inventory = $item['qty'];
                $order_item->post_ID = $product->post_ID;
                $order_item->metas =array(
                    'title'=>$product->get_title(),
                    'img'=>$product->get_img(),
                    'link'=>$product->get_link(),
                    'post_type'=>$product->post->post_type
                );
                return $order_item;
            },$order,$item);
            
            $order_item_creator = apply_filters("wshop_{$post_type}_order_item_creator",$order_item_creator,$order,$item);    
            $order_item_creator = apply_filters("wshop_{$section}_order_item_creator",$order_item_creator,$order,$item);
            
            $order_item =call_user_func_array($order_item_creator, array($order,$item));
            if($order_item instanceof WShop_Error){
                return $order_item;
            }
            $order->order_items[]=$order_item;
            $order->total_amount +=$order_item->get_subtotal(false);
        }
      
        //init extra_amount
        $order->extra_amount = array();
        $error=apply_filters('wshop_order_extra_amount',WShop_Error::success(),$order,$this);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        foreach ($order->extra_amount as $label=>$atts){
            $order->total_amount+=$atts['amount'];
        }
       
        $error = $order->insert();
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        $error = $order->call_after_insert();
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        //order items
        foreach ($order->order_items as $order_item){
            $order_item->order_id = $order->id;
    
            $error = $order_item->insert();
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            $error = apply_filters('wshop_order_item_created', WShop_Error::success(),$order_item,$order,$this);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            $error = apply_filters("wshop_order_item_{$order->obj_type}_created", WShop_Error::success(),$order_item,$order,$this);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
            
            if($on_order_item_created){
                $error = call_user_func_array($on_order_item_created, array($order,$order_item));
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
            }
        }
       
        $error = apply_filters('wshop_order_created', WShop_Error::success(),$order,$this);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        $error = apply_filters("wshop_order_{$order->obj_type}_created", WShop_Error::success(),$order,$this);
        if(!WShop_Error::is_valid($error)){
            return $error;
        }
        
        if($on_order_created){
            $error = call_user_func($on_order_created, $order);
            if(!WShop_Error::is_valid($error)){
                return $error;
            }
        }
        
        $this->__set_order($order->id);       
        $error = $this->save_changes();     
        if($error instanceof WShop_Error){
            return $error;
        }
        
        return $order;
    }
    
    /**
     * 
     * @param int $post_id
     * @param number $qty
     * @param function $func_inventory_valid
     * @return WShop_Shopping_Cart
     */
    public function __add_to_cart($post_id,$qty =1,$func_inventory_valid=null,$metas=array()){
        $product = new WShop_Product($post_id);
        if(!$product->is_load()){
            return WShop_Error::error_custom(__('Product info is invalid!',WSHOP));
        }
        if(!is_numeric($qty)){$qty=1;}
        if(!$this->items||!is_array($this->items)){$this->items=array();}

        $new_items = array();
        foreach ($this->items as $post_ID=>$settings){
            $_product = new WShop_Product($post_id);
            if(!$_product->is_load()||$_product->get('post_status')!='publish'||$_product->get('post_type')!=$product->get('post_type')){
                continue;
            }
        
            $new_items[$post_ID] = $settings;
        }
       
        $this->items=$new_items;
        unset($new_items);
        
        if(!$func_inventory_valid){
            $func_inventory_valid = function($cart,$product,$qty,$metas){ 
                $oqty = isset($cart->items[$product->post_ID]['qty'])? absint($cart->items[$product->post_ID]['qty']):0;   
                $now_qty =$oqty+$qty;
                
                $cart->items[$product->post_ID]['qty']=$now_qty;
                if(isset($cart->items[$product->post_ID]['metas'])&&$cart->items[$product->post_ID]['metas']&&is_array($cart->items[$product->post_ID]['metas'])){
                    $cart->items[$product->post_ID]['metas'] = array_merge($cart->items[$product->post_ID]['metas'],$metas);
                }else{
                    $cart->items[$product->post_ID]['metas']=$metas;
                }
                
                $inventory = $product->get('inventory');
                if(!is_null($inventory)) {
                    if($inventory-$now_qty<0){
                        return WShop_Error::error_custom(__('Product is understock!',WSHOP));
                    }
                }
            
                return apply_filters('wshop_cart_item_validate', WShop_Error::success(),$cart,$product,$now_qty);
            };
        }
    
        $error = call_user_func_array($func_inventory_valid, array($this,$product,$qty,$metas));
        if(!WShop_Error::is_valid($error)){
            throw new Exception($error->errmsg,$error->errcode);
        }
       
        
        $this->set_change('obj_type', $product->get('post_type'));
        return $this->set_change('items', $this->items);
    }
    
    public function __change_to_cart($post_id,$qty =1,$func_inventory_valid=null){
        $product = new WShop_Product($post_id);
        if(!$product->is_load()){
            return WShop_Error::error_custom(__('Product info is invalid!',WSHOP));
        }
    
        if(!$this->items||!is_array($this->items)){$this->items=array();}
    
        $new_items = array();
        foreach ($this->items as $post_ID=>$settings){
            $_product = new WShop_Product($post_id);
            if(!$_product->is_load()||$_product->get('post_status')!='publish'||$_product->get('post_type')!=$product->get('post_type')){
                continue;
            }
    
            $new_items[$post_ID] = $settings;
        }
         
        $this->items=$new_items;
        unset($new_items);
    
        if(!$func_inventory_valid){
            $func_inventory_valid = function($cart,$product,$qty){
                $now_qty =$qty;
                $cart->items[$product->post_ID]['qty']=$now_qty;
    
                $inventory = $product->get('inventory');
                if(!is_null($inventory)) {
                    if($inventory-$now_qty<0){
                        return WShop_Error::error_custom(__('Product is understock!',WSHOP));
                    }
                }
    
                return apply_filters('wshop_cart_item_validate', WShop_Error::success(),$cart,$product,$now_qty);
            };
        }
   
        $error = call_user_func_array($func_inventory_valid, array($this,$product,$qty));
        if(!WShop_Error::is_valid($error)){
             throw new Exception($error->errmsg,$error->errcode);
        }
    
        $this->set_change('obj_type', $product->get('post_type'));
        return $this->set_change('items', $this->items);
    }
}