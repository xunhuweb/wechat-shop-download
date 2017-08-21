<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

abstract class Abstract_WShop_Shopping_Cart extends WShop_Object{
    public $id;
    public $customer_id;
    public $items =array();
    public $coupons=array();
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
        return 'wshop_shopping_cart';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    { 
        return apply_filters('wshop_shopping_cart_properties', array(
            'id'=>null,
            'customer_id'=>null,
            'items'=>array(),
            'created_time'=>current_time( 'timestamp' ),
            'coupons'=>array()
        ));
    }
    
    /**
     * 获取购物车内容
     * @since 1.0.0
     * @return array
     */
    public function get_items(){
        $results = array();
        if($this->items){
            foreach ($this->items as $post_id=>$atts){
                $qty = intval(isset($atts['qty'])?$atts['qty']:0);
                if($qty<=0){
                    continue;
                }
                $product = new WShop_Product($post_id);
                if(!$product->is_load()){
                    continue;
                }
                
                $results[$post_id] =array(
                    'product'=>$product,
                    'qty'=>intval($qty)
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
        $total=0;
        foreach ($items as $post_id=>$item){
            $total+=$item['product']->get_single_price(false)*$item['qty'];
        }
        
        return round($total,2);
    }
    
}