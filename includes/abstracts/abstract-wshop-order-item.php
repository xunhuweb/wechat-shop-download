<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

abstract  class Abstract_WShop_Order_Item extends WShop_Mixed_Object{
    public function __construct($wp_order_item=null){
        parent::__construct($wp_order_item);
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
        return "id";
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
        // TODO Auto-generated method stub
        return "wshop_order_item";
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    {
        // TODO Auto-generated method stub
        return apply_filters('wshop_order_item_properties',array(
            'id'=>0,
            'order_id'=>$this->id,
            'qty'=>1,
            'price'=>0.00,
            'post_ID'=>null,
            'class'=>get_called_class(),
            'currency'=>WShop::instance()->payment->get_currency(),
            'metas'=>array()
        ));
    }

    public $id;
    
    public $order_id;
    
    /**
     * post ID
     * @var int
     */
    public $post_ID;
    
    /**
     * 商品单价
     * @var float
     */
    public $price;
    
    /**
     * 购买数量
     * @var int
     */
    public $qty;
    
    /**
     * 货币
     * @var string
     */
    public $currency;
    
    public $metas = array();
    
   
 
    /**
     * @param Abstract_WShop_Order_Item $order_item
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_pre_order_item_instert($order,$request){
        return apply_filters('wshop_order_pre_order_item_instert', WShop_Error::success(),$this,$order,$request);
    }
    
    /**
     * @param Abstract_WShop_Order_Item $order_item
     * @return WShop_Error
     * @since 1.0.0
     */
    public function on_after_order_item_instert($order,$request){
        return apply_filters('wshop_order_after_order_item_instert', WShop_Error::success(),$this,$order,$request);
    }
    
    /**
     * 获取商品价格
     * @param boolean $symbol
     * @return string|number
     * @since 1.0.0
     */
    public function get_price($symbol=false){
        $amount =$this->price;
        
        if($symbol){
            $symbol =WShop_Currency::get_currency_symbol($this->currency);
            $amount = "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($this->price);
        }
        
        return $amount;
    }
    
    /**
     * 获取商品总价
     * @param boolean $symbol
     * @return string|number
     * @since 1.0.0
     */
    public function get_subtotal($symbol=false){
        $amount =$this->price*$this->qty;
    
        if($symbol){
            $symbol =WShop_Currency::get_currency_symbol($this->currency);
            $amount = "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($this->price*$this->qty);
        }
    
        return $amount;
    }
    
    /**
     * @since 1.0.0
     * @return string
     */
    public function get_img(){
        return $this->metas&&isset($this->metas['img'])?$this->metas['img']:null;
    }
    
    /**
     * @since 1.0.0
     * @return string
     */
    public function get_title(){
        return $this->metas&&isset($this->metas['title'])?$this->metas['title']:null;
    }
    
    /**
     * @since 1.0.0
     * @return string
     */
    public function get_link(){
        return $this->metas&&isset($this->metas['link'])?$this->metas['link']:null;
    }
    
}

class WShop_Order_Item_Model extends Abstract_WShop_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Model_Api::init()
     */
    public function init()
    {
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_order_item` (
            	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            	`order_id` BIGINT(20) NOT NULL,
            	`qty` INT(11) NOT NULL DEFAULT '0',
            	`price` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
            	`post_ID` BIGINT(20) NOT NULL DEFAULT '0',
            	`class` VARCHAR(64) NOT NULL, 
            	`metas` text NULL DEFAULT NULL, 
            	`currency` VARCHAR(6) NOT NULL DEFAULT 'CNY',
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