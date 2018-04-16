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
        
        $this->post = new WP_Post($this);
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
            'inventory'=>null,
            'sale_qty'=>0,
            'sale_price'=>0
        ),$this);
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_link(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
       
        return apply_filters('wshop_product_link', get_post_permalink($this->post->ID),$this);
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
        if(!$thumbnail_id){
            $thumbnail_id= get_post_thumbnail_id(WShop_Settings_Default_Basic_Default::instance()->get_option('product_img_default',0));
        }
        
        $thumb= $thumbnail_id?wp_get_attachment_image_src($thumbnail_id, 'thumbnail'):null;
         
        return apply_filters('wshop_product_img', $thumb&&count($thumb)>0?$thumb[0]:WSHOP_URL.'/assets/image/default.png',$this);
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_title(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
    
        return apply_filters('wshop_product_title', $this->post->post_title,$this);
    }
    
    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function get_desc(){
        if(!$this->is_load()){
            throw new Exception('Post is not load!!');
        }
         
        return apply_filters('wshop_product_desc', $this->post->post_excerpt,$this);
    }
    
    public function get_inventory(){
        return $this->get('inventory');
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
    
        $osale_price = $sale_price =apply_filters('wshop_product_single_price', isset($this->sale_price)? round(floatval($this->sale_price),2):0.00,$this->post); 
        
        if($symbol){
            $symbol_txt =$this->get_currency_symbol();
            $sale_price = $symbol_txt.WShop_Helper_String::get_format_price($sale_price);
        }

        return apply_filters('wshop_product_single_price_html',$sale_price,$osale_price,$symbol,$this);
    }
    
    /**
     * 
     * @return 获取货币符号
     * @since 1.0.4
     */
    public function get_currency_symbol(){
        return WShop_Currency::get_currency_symbol(WShop::instance()->payment->get_currency());
    }

    /**
     * 购物车html
     * @return string
     * @since 1.0.0
     */
    public function shopping_cart_item_html($shopping_cart,$qty,$context){
        if(!$this->is_load()){
            ob_start();
            WShop::instance()->WP->wp_die(__('Post is not found!',WSHOP));
            return ob_get_clean();
        }
        
        $html = apply_filters("wshop_product_shopping_cart_item_html", null,$this,$shopping_cart,$qty,$context);
        if(!empty($html)){
            return $html;
        }

        return WShop::instance()->WP->requires(WSHOP_DIR, 'product/shopping-cart-item.php',array(
            'context'=>$context,
            'cart'=>$shopping_cart,
            'qty'=>$qty,
            'product'=>$this
        ));
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
                `inventory`  int(11) NULL DEFAULT NULL,
                `sale_qty`  int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`post_ID`)
            ) 
            $collate;");

        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
           "select column_name
			from information_schema.columns
			where table_name='{$wpdb->prefix}wshop_product'
					and table_schema ='".DB_NAME."'
					and column_name ='inventory'
			limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_product` add column `inventory` int(11) NULL DEFAULT NULL;");
        }
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        $column =$wpdb->get_row(
           "select column_name
            from information_schema.columns
            where table_name='{$wpdb->prefix}wshop_product'
            and table_schema ='".DB_NAME."'
								and column_name ='sale_qty'
						limit 1;");
        
        if(!$column||empty($column->column_name)){
            $wpdb->query("alter table `{$wpdb->prefix}wshop_product` add column `sale_qty` int(11) NOT NULL DEFAULT 0;");
        }
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

    public function manage_posts_custom_column($column,$post_ID){
        global $current_wshop_product;
        if(!$current_wshop_product||$current_wshop_product->post_ID!=$post_ID){
            $current_wshop_product =  new WShop_Product($post_ID);
        }
        
        $product =$current_wshop_product;
        if($column=='wshop_sale_price'){
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
    public function init_form_fields(){
        global $post;
        $settings=array();
        
        $settings = apply_filters('wshop_product_fields0', $settings,$post,$this);
       
        $settings['sale_price']=array(
            'title'=>__('Sale price',WSHOP),
            'type'=>'custom',
            //'default'=>'123',
            'required'=>true,
            'func'=>function($key,$api,$data){
                $field = $api->get_field_key ( $key );
                $defaults = array (
                    'title' => '',
                    'disabled' => false,
                    'class' => '',
                    'css' => '',
                    'placeholder' => '',
                    'type' => 'text',
                    'desc_tip' => false,
                    'description' => '',
                    'custom_attributes' => array ()
                );
                
                $data = wp_parse_args ( $data, $defaults );
                ?>
                <tr valign="top" class="<?php echo isset($data['tr_css'])?$data['tr_css']:''; ?>">
                	<th scope="row" class="titledesc">
                		<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <span style="color:red;">*</span></label>
                		<?php echo $api->get_tooltip_html( $data ); ?>
                	</th>
                	<td class="forminp">
                		<fieldset>
                			<legend class="screen-reader-text">
                				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
                			</legend>
                			<?php $symbol =WShop_Currency::get_currency_symbol(WShop::instance()->payment->get_currency());?>
                			<?php echo $symbol?> <input class="wc_input_decimal input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( ( $api->get_option( $key ) ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $api->get_custom_attribute_html( $data ); ?> />
                			<?php echo $api->get_description_html( $data ); ?>
                		</fieldset>
                	</td>
                </tr>
                <?php
            },
            'validate'=>function($key,$api){
                $field = $api->get_field_key ( $key );
                return isset($_POST[$field])?round( floatval($_POST[$field]),2):0;
            }
        );
        $settings = apply_filters('wshop_product_fields1', $settings,$post,$this);
        if(apply_filters('wshop_enable_inventory', WShop_Settings_Checkout_Options::instance()->get_option('enable_inventory')==='yes',$post)){
            $settings['inventory']=array(
                'title'=>__('Inventory',WSHOP),
                'type'=>'number',
                //'default'=>'123',
                'description'=>'如果留空，那么当前产品每次下单只能购买一个；如果整数值，那么每次下单，当前库存将减少',
                'validate'=>function($key,$api){
                    $field = $api->get_field_key ( $key );
                    return isset($_POST[$field])&&$_POST[$field]!='' ?intval($_POST[$field]):null;
                }
            );
       
            $settings['sale_qty']=array(
                'title'=>__('Sold qty',WSHOP),
                'type'=>'number',
                'custom_attributes'=>array('readonly'=>'readonly'),
                'default'=>'0',
                'validate'=>function($key,$api){
                    $field = $api->get_field_key ( $key );
                    return isset($_POST[$field])&&$_POST[$field]!='' ?intval($_POST[$field]):null;
                }
            );
        }
        
        $this->form_fields = apply_filters('wshop_product_fields2', $settings,$post,$this);
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