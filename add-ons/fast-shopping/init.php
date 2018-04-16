<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * @author rain
 *
 */
class WShop_Modal_Fast_Shopping extends Abstract_WShop_Add_Ons{
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
        $this->id='wshop_add_ons_fast_shopping';
        $this->title=__('Fast Shopping',WSHOP);
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->description='给文章添加一个收款按钮';    
        $this->author=__('xunhuweb',WSHOP);
        $this->author_uri='https://www.wpweixin.net';
        $this->setting_uris = array(
            'settings'=>array(
                'title'=>__('Settings',WSHOP),
                'url'=>admin_url('admin.php?page=wshop_page_default&section=menu_default_modal&sub=wshop_add_ons_fast_shopping')
            )
        );
        $this->init_form_fields();
    }

    /**
     * @since 1.0.0
     * {@inheritDoc}
     * @see Abstract_WShop_Settings::init_form_fields()
     */
    public function init_form_fields(){
        $fields =array(
            'post_types'=>array(
                'title'=>__('Bind post types',WSHOP),
                'type'=>'multiselect',
                'func'=>true,
                'options'=>array($this,'get_post_type_options')
            )
        );
  
        $this->form_fields = apply_filters('wshop_pay_fields', $fields);
    }
  
    public function on_load(){
        add_filter('wshop_product_fields1', array($this,'wshop_product_fields'),10,2);
    }
    
    /**
     * @since 1.0.0
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_init()
     */
    public function on_init(){
        add_filter('wshop_admin_menu_menu_default_modal',function($menus){
            $menus[]=WShop_Modal_Fast_Shopping::instance();
            return $menus;
        },10,1);
       
        $api =$this;
        add_filter('wshop_online_post_types',array($api,'wshop_online_post_types'),10,1);
        WShop_Async::instance()->async('wshop_btn', array($api,'wshop_btn'));
    }
    
    
    public function wshop_online_post_types($post_types){
        $types = $this->get_option('post_types');
         
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
        $post_types = $this->get_option('post_types',array());
        if(!$post_types||!is_array($post_types)){
            $post_types = array();
        }
        
        if(!in_array($post->post_type, $post_types)){
            return $form_settings;
        }
        
        $form_settings['purchase_btn']=array(
            'title'=>__('Purchase button',WSHOP),
            'type'=>'custom',
            'ignore'=>true,
            'func'=>function( $key,$api,$data){
                global $post;
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
                			<a href="" target="_blank"><code>[wshop_btn]</code></a> <a class="wshop-btn-insert" href="javascript:void(0);" onclick="window.wshop_post_editor.add_content('[wshop_btn post_id=\'<?php echo $post->ID?>\']立即购买[/wshop_btn]');"><?php echo __('Insert into post content',WSHOP)?></a>
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
           if(!is_array($atts)){
               $atts = array();
           }
            if(!isset($atts['location'])||empty($atts['location'])){
                $atts['location'] =  WShop_Helper_Uri::get_location_uri();
            }
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                 global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
           
        },function(&$atts,&$content){
            //付费后隐藏
            if(isset($atts['ph'])&&"{$atts['ph']}"==="1"){
                if(WShop::instance()->payment->is_validate_get_pay_per_view($atts['post_id'],empty($atts['roles'])?null:explode(',',$atts['roles'] ))){
                    return null;
                }
            }
            $content=empty($content)?__('Pay Now',WSHOP):$content;
            
            return WShop::instance()->WP->requires(WSHOP_DIR, 'button-purchase.php',array(
                'content'=>$content,
                'atts'=>$atts
            ));
        },
        array(
            'style'=>null,
            'post_id'=>0,
            'modal'=>null,
            'roles'=>null,
            'ph'=>0,//支付后隐藏
            'class'=>'xh-btn xh-btn-danger xh-btn-lg',
            'location'=>null
        ),
        $atts,
        $content);
    }
}


if(!function_exists('wshop_btn')){
    function wshop_btn($atts=array(),$content=null,$echo=true){
        $html =WShop_Modal_Fast_Shopping::instance()->wshop_btn($atts,$content);
        if($echo) echo $html;return $html;
    }
}

return WShop_Modal_Fast_Shopping::instance();
?>