<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

abstract class Abstract_WShop_Product extends WShop_Post_Object{
    /**
     * POST
     * @var WP_Post
     */
    public $post;
    
    /**
     * 实例
     * @param int|WP_Post $post
     */
    public function __construct($post=null){
        parent::__construct($post);
        
        $this->post = $this->get_post();
    }
    
    public function is_load(){
        if(!$this->post){
            return false;
        }
        
        $onlines = WShop::instance()->payment->get_online_post_types();
        if(!isset($onlines[$this->post->post_type])){
           return false;
        }
        return parent::is_load();
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_table_name()
     */
    public function get_table_name()
    {
        // TODO Auto-generated method stub
        return 'wshop_product';
    }
    
    /**
     * {@inheritDoc}
     * @see WShop_Object::get_propertys()
     */
    public function get_propertys()
    {
        // TODO Auto-generated method stub
        return apply_filters('wshop_product_properties', array(
            'post_ID'=>0,
            'sale_price'=>0
        ));
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_link(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
         
        return apply_filters('wshop_product_link', get_post_permalink($this->post->ID));
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_img(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
         
        $thumbnail_id = get_post_thumbnail_id($this->post->ID);
        $thumb= $thumbnail_id?wp_get_attachment_image_src($thumbnail_id, 'thumbnail'):null;
         
        return apply_filters('wshop_product_img', $thumb&&count($thumb)>0?$thumb[0]:"");
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_title(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
    
        return apply_filters('wshop_product_title', $this->post->post_title);
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_desc(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
         
        return apply_filters('wshop_product_desc', $this->post->post_excerpt);
    }
    
    /**
     * 获取销售价格
     * @return float
     * @since 1.0.0
     */
    public function get_single_price($symbol=false){
        if(!$this->is_load()){
            throw new Exception('post is not loaded!');
        }
    
        $sale_price = isset($this->sale_price)? round(floatval($this->sale_price),2):0.00;
         
        if($symbol){
            $symbol =WShop_Currency::get_currency_symbol(WShop::instance()->payment->get_currency());
            $sale_price = "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($sale_price);
        }
    
        return $sale_price;
    }

    /**
     * 购物车html
     * @return string
     * @since 1.0.0
     */
    public function shopping_cart_item_html($shopping_cart,$qty,$request){
        if(!$this->is_load()){
            ob_start();
            WShop::instance()->WP->wp_die(__('Post is not found!',WSHOP));
            return ob_get_clean();
        }
        
        $html = apply_filters('wshop_product_shopping_cart_item_html', null,$this,$shopping_cart,$qty,$request);
        if(!empty($html)){
            return $html;
        }
        
        return WShop::instance()->WP->requires(WSHOP_DIR, 'product/shopping-cart-item.php',array(
            'cart'=>$shopping_cart,
            'qty'=>$qty,
            'request'=>$request,
            'product'=>$this
        ));
    }
    
    /**
     * @param WShop_Shopping_Cart $cart
     * @param array $request
     */
    public function to_order_item($cart,$request){
        if(!$this->is_load()){
            return WShop_Error::error_custom('Post is not found!!',WSHOP);
        }
        
        $order_item =new WShop_Order_Item();
        $meta = isset($cart->items[$this->post->ID])?$cart->items[$this->post->ID]:null;
        if(!$meta){
            return WShop_Error::error_custom('invalid shopping cart item!',WSHOP);
        }
        
        $order_item->price = $this->get_single_price(false);
        $order_item->qty =$meta['qty'];
        $order_item->post_ID = $this->post->ID;
        $order_item->metas =array(
            'title'=>$this->get_title(),
            'img'=>$this->get_img(),
            'link'=>$this->get_link()
        );
        
        return $order_item;
    }
}

class WShop_Product_Model extends Abstract_WShop_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Model_Api::init()
     */
    public function init()
    {
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wshop_product` (
                `post_ID` BIGINT(20) NOT NULL,
                `sale_price` decimal(12,2) NOT NULL DEFAULT '0.00',
                PRIMARY KEY (`post_ID`)
            ) 
            $collate;");

        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}

class WShop_Product_Fields extends Abstract_XH_WShop_Fields{
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
     * @return WShop_Product_Fields - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * post 设置区域
     *
     * @param WShop_Payment_Api $payment
     * @since 1.0.0
     */
    protected function __construct(){
        parent::__construct();
        $this->id="product";
        $this->title = __('Price settings',WSHOP);
    }

    public function admin_init(){
        parent::admin_init();
        foreach ($this->get_post_types() as $post_type=>$label){
            add_filter( "manage_{$post_type}_posts_columns", array($this,'manage_posts_columns'),11 ,1);
            add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'manage_posts_custom_column' ),11, 2 );
        }
    }


    public function manage_posts_columns($existing_columns){
        if(!$existing_columns){$existing_columns=array();}

        $has = false;
        $new_columns = array();
        foreach ($existing_columns as $key=>$v){
            $new_columns[$key]=$v;
            if($key=='title'){
                $new_columns['wshop_sale_price']=__('Sale price',WSHOP);
                $has=true;
            }
        }

        return $new_columns;
    }

    public function validate_sale_price_field($key){
        $field = $this->get_field_key ( $key );
        return isset($_POST[$field])?round( floatval($_POST[$field]),2):0;
    }

    public function manage_posts_custom_column($column,$post_ID){
        if($column=='wshop_sale_price'){
            $product = new WShop_Product($post_ID);
            if($product->is_load()){
                echo $product->get_single_price(true);
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see Abstract_WShop_Settings::init_form_fields()
     * @since 1.0.0
     */
    public function _init_form_fields($post){
        $this->form_fields = apply_filters('wshop_product_fields', array(
            'sale_price'=>array(
                'title'=>__('Sale price',WSHOP),
                'type'=>'decimal'
            )
        ),$post,$this);
    }

    /**
     * {@inheritDoc}
     * @see Abstract_XH_WShop_Fields::get_post_types()
     */
    public function get_post_types()
    {
        return WShop::instance()->payment->get_online_post_types();
    }

    public function get_object($post){
        return new WShop_Product($post->ID);
    }

}
?>