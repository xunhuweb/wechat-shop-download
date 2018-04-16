<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

abstract class Abstract_WShop_Shopping_Cart extends WShop_Object{
    public $id;
    public $obj_type;
    public $customer_id;
    public $order_id;
    public $payment_method;
    /**
     * 
     * @var array
     */
    public $items =array();
    /**
     *
     * @var array
     */
    public $coupons=array();
    
    /**
     * 
     * @var array
     */
    public $metas = array();
    
    /**
     *
     * @var int
     */
    public $created_time;

    /**
     * @param object $wp_order 数据库中查询的data
     */
    public function __construct($wp_cart=null)
    {
        parent::__construct($wp_cart);
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::is_auto_increment()
     */
    public function is_auto_increment()
    {
        // TODO Auto-generated method stub
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_primary_key()
     */
    public function get_primary_key()
    {
        // TODO Auto-generated method stub
        return 'customer_id';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
        // TODO Auto-generated method stub
        return 'wshop_shopping_carts';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    { 
        return apply_filters('wshop_shopping_cart_properties', array(
            'customer_id'=>null,
            'obj_type'=>null,
            'order_id'=>null,
            'items'=>array(),
            'metas'=>array(),
            'payment_method'=>null,
            'created_time'=>current_time( 'timestamp' ),
            'coupons'=>array()
        ));
    }
    
    /**
     * 获取购物车内容
     * @since 1.0.0
     * @return array|WShop_Error
     */
    public function get_items($refresh_cached = false){
        $results = array();
        if($this->items&&is_array($this->items)){
            $post_type = null;
            $product_first = null;
            foreach ($this->items as $post_id=>$atts){
                if($refresh_cached){
                    clean_post_cache($post_id);
                }
                
                $qty = absint($atts['qty']);
                if($qty<=0){
                    continue;
                }
                
                $product = new WShop_Product($post_id);
                if(!$product->is_load()){
                    continue;
                }
                
                $inventory = $product->get('inventory');
                if(!is_null($inventory)) {
                    if($inventory-$qty<0){
                       continue;
                    }
                }
                
                $error = apply_filters('wshop_cart_item_validate', WShop_Error::success(),$this,$product,$qty);
                if(!WShop_Error::is_valid($error)){
                    return $error;
                }
                
                $results[$post_id] =array(
                    'product'=>$product,
                    'qty'=>$qty,
                    'metas'=>isset($atts['metas'])&&$atts['metas']&&is_array($atts['metas'])?$atts['metas']:array()
                );
            }
        }
        
        return $results;
    }
    
    /**
     * 获取购物车总价格
     * @since 1.0.0
     * @return number
     */
    public function get_total(){
        $items = $this->get_items();
        if($items instanceof WShop_Error){
            self::empty_cart();
            return 0;
        }
        
        $total=0;
        foreach ($items as $post_id=>$item){
            $total+=$item['product']->get_single_price(false)*$item['qty'];
        }
        
        return round($total,2);
    }

    public static function empty_cart(){
        $cart = WShop_Shopping_Cart::get_cart();
        if($cart instanceof WShop_Error){
            return $cart;
        }
    
        return $cart->__empty_cart()->save_changes();
    }
    
}