<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

require_once 'includes/class-wshop-download.php';	

/**
 * @author rain
 *
 */
class WShop_Add_On_Download extends Abstract_WShop_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Add_On_Download
     */
    private static $_instance = null;

    /**
     * 插件跟路径url
     * @var string
     * @since 1.0.0
     */
    public $domain_url;
    public $domain_dir;
    
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return WShop_Add_On_Download
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='wshop_add_ons_download';
        $this->title=__('Pay per download',WSHOP);
        $this->description='将下载地址插入文章，付费后才能看到下载内容';
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',WSHOP);
        $this->author_uri='https://www.wpweixin.net';
        $this->domain_url = WShop_Helper_Uri::wp_url(__FILE__) ;
        $this->domain_dir = WShop_Helper_Uri::wp_dir(__FILE__) ;
     
        $this->init_form_fields();    
        $this->enabled = 'yes'==$this->get_option('enabled');
    }
    
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
                'options'=>array($this,'get_post_type_options')
            )
        );
        
        $this->form_fields = apply_filters('wshop_download_fields', $fields);
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
    
    
    public function on_install(){
        $model = new WShop_Download_Model();
        $model->init();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_load()
     */
    public function on_load(){
        $o = $this;
        add_filter('wshop_enable_guest', array($o,'wshop_enable_guest'),10,2);
        add_filter('wshop_order_received_url', array($o,'wshop_order_received_url'),10,2);
        add_filter('wshop_admin_menu_menu_default_modal', function ($menus) {
            $menus[] = WShop_Add_On_Download::instance();
            return $menus;
        }, 12, 1);
        
        WShop_Async::instance()->async('wshop_downloads', array($o,'wshop_downloads'));
        
        if($o->enabled){
            add_filter('wshop_online_post_types', array($o,'wshop_online_post_types'));
            add_action('wshop_after_init', function(){
                WShop_Download_Field::instance();
            },10);
        }
    }
    
    public function wshop_enable_guest($enable_guest,$atts){
        if(!$enable_guest){
            return $enable_guest;
        }
    
        $post_id = isset($atts['post_id'])?$atts['post_id']:null;
        $post = get_post($post_id);
        if(!$post){
            return $enable_guest;
        }
    
        $types = $this->get_option('post_types');
        if(in_array($post->post_type, $types)){
            return false;
        }
    
        return $enable_guest;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_init()
     */
    public function on_init(){
            $o = $this;
           
            add_filter('wshop_download_atts', array($o,'wshop_download_atts'),10,1);
            $o->setting_uris = array(
                'settings'=>array(
                    'title'=>__('Settings',WSHOP),
                    'url'=>admin_url('admin.php?page=wshop_page_default&section=menu_default_modal&sub=add_ons_download')
                )
            );
    }
    
    public function wshop_order_received_url($url,$order){
        if(!isset($order->metas['location'])||empty($order->metas['location'])){
            return $url;
        }
        
        $post_types = $this->get_option('post_types');
        $order_items = $order->get_order_items();
        if($order_items){
            foreach ($order_items as $order_item){
                $post = get_post($order_item->post_ID);
                if($post&&in_array($post->post_type,$post_types)){
                    return $order->metas['location'];
                }
            }
        }
    
        return $url;
    }
    
    public function wshop_download_atts($m){
       $m['style']=null;
       $m['post_id']=0;
       $m['class']='xh-btn xh-btn-primary';
       return $m;
    }
   
    public function wshop_downloads($atts = array(),$content=null){
        return WShop_Async::instance()->async_call('wshop_downloads', function(&$atts,&$content){
            if(!isset($atts['post_id'])||empty($atts['post_id'])){
                global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
            
            if(!isset($atts['location'])||empty($atts['location'])){
                $atts['location'] =  WShop_Helper_Uri::get_location_uri();
            }
        
        },function(&$atts,&$content){
            $content = empty($content)?__('Download now',WSHOP):$content;
           
            $post_id = isset($atts['post_id'])?$atts['post_id']:null;
            if(!$post_id){return null;}
            
            $download = new WShop_Download($post_id);      
            if(!$download->is_load()){
                return null;
            }
        
            $is_validate = apply_filters('wshop_download_is_validate_get_data', false,$atts);
            if($is_validate){
                return $download->downloads;
            }
        
            if(WShop::instance()->payment->is_validate_get_pay_per_view($post_id,isset($atts['roles'])?explode(',', $atts['roles']):null)){
                return $download->downloads;
            }
        
            return WShop::instance()->WP->requires(WSHOP_DIR, 'button-purchase.php',array(
                'content'=>$content,
                'atts'=>$atts
            ));
        },
        apply_filters('wshop_download_atts',WShop::instance()->payment->pay_atts()),
        $atts,
        $content); 
    }
}

if(!function_exists('wshop_downloads')){
    function wshop_downloads($atts = array(),$content=null,$echo = true){
        $html = WShop_Add_On_Download::instance()->wshop_downloads($atts,$content);
        if($echo){echo $html;}else{return $html;}
    }
}
return WShop_Add_On_Download::instance();
?>