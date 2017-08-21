<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * @author rain
 *
 */
class WShop_Modal_Fast_Shopping extends Abstract_WShop_Settings{   
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Modal_Fast_Shopping
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return WShop_Modal_Fast_Shopping
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='modal_fast_shopping';
        $this->title=__('Fast Shopping',WSHOP);
        $this->description='';        
        $this->init_form_fields();
        
        $this->enabled = 'yes'==$this->get_option('enabled');
    }

    /**
     * @since 1.0.0
     * {@inheritDoc}
     * @see Abstract_WShop_Settings::init_form_fields()
     */
    public function init_form_fields(){
        $fields =array(
            'enabled'=>array(
                'title'=>__('Enable/Disable',WSHOP),
                'type'=>'checkbox',
                'default'=>'yes'
            ),
            'post_types'=>array(
                'title'=>__('Bind post types',WSHOP),
                'type'=>'multiselect',
                'func'=>true,
                'default'=>array('post'),
                'options'=>array($this,'get_post_type_options')
            )
        );
  
        $this->form_fields = apply_filters('wshop_pay_fields', $fields);
    }
    
    public function get_post_type_options(){
        return apply_filters('wshop_modal_fast_shopping_post_types', parent::get_post_type_options());
    }
    
    /**
     * @since 1.0.0
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_init()
     */
    public static function on_init(){
        add_filter('wshop_admin_menu_menu_default_modal',function($menus){
            $menus[]=WShop_Modal_Fast_Shopping::instance();
            return $menus;
        },10,1);
        
        add_filter('wshop_btn_atts', function($m){
            $m['class']="xh-btn xh-btn-primary";
            $m['style']=null;
            $m['post_id']=null;
            return $m;
        },10,1);
        
        $api =WShop_Modal_Fast_Shopping::instance();
        if($api->enabled){
            add_filter('wshop_online_post_types',array($api,'wshop_online_post_types'),10,1);
            WShop_Async::instance()->async('wshop_btn', array($api,'wshop_btn'));
            add_filter('wshop_product_fields', array($api,'wshop_product_fields'),10,2);
        }
    }
    
    
    public function wshop_online_post_types($post_types){
        $types = WShop_Modal_Fast_Shopping::instance()->get_option('post_types');
         
        if($types){
            foreach ($types as $type){
                if(!in_array($type, $post_types)){
                    $post_types[]=$type;
                }
            }
        }
         
        return $post_types;
    }
    public function wshop_product_fields($form_settings,$post){
        $types = WShop_Modal_Fast_Shopping::instance()->get_option('post_types');
        if(!apply_filters('wshop_product_fields_show_purchase_btn', in_array($post->post_type, $types),$post)){
            return $form_settings;
        }

        $form_settings['purchase_btn']=array(
            'title'=>__('Purchase button',WSHOP),
            'type'=>'custom',
            'ignore'=>true,
            'func'=>function( $key,$api){
            ?>
                <tr valign="top" class="">
                	<th scope="row" class="titledesc">
                		<label for="wshop_1_sale_price"><?php echo __('Purchase button',WSHOP)?></label>
                	</th>
                	<td class="forminp">
                		<fieldset>
                			<legend class="screen-reader-text">
                				<span><?php echo __('Purchase button',WSHOP)?></span>
                			</legend>
                			<a href="" target="_blank"><code>[wshop_btn]</code></a> <a class="wshop-btn-insert" href="javascript:void(0);" onclick="window.wshop_post_editor.add_content('[wshop_btn]');"><?php echo __('Insert into post content',WSHOP)?></a>
                			<p class="description"></p>
                		</fieldset>
                	</td>
                </tr>
                <?php               
            }
        );
        
        return $form_settings;
    }

    /**
     * 生成按钮支付html
     * @param array $atts 
     * @param string $content
     * @since 1.0.0
     */
    public function wshop_btn($atts=array(),$content = null){ 
       return WShop_Async::instance()->async_call('wshop_btn', function(&$atts,&$content){
            if(!isset($atts['location'])||empty($atts['location'])){
                $atts['location'] =  WShop_Helper_Uri::get_location_uri();
            }
            
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                 global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
            
        },function(&$atts,&$content){
            $content=empty($content)?__('Pay Now',WSHOP):$content;
            
            return WShop::instance()->WP->requires(WSHOP_DIR, 'modal/fast-shopping/fast-shopping-btn.php',array(
                'content'=>$content,
                'atts'=>$atts
            ));
        },
        apply_filters('wshop_btn_atts',WShop::instance()->payment->pay_atts()),
        $atts,
        $content);
        
    }
}
?>