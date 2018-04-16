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
    }
    
    public function init_form_fields(){
        $fields =array(
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
        add_filter('wshop_order_download_received_url', array($o,'wshop_order_received_url'),10,2);
        add_filter('wshop_admin_menu_menu_default_modal', function ($menus) {
            $menus[] = WShop_Add_On_Download::instance();
            return $menus;
        }, 12, 1);
        
        WShop_Async::instance()->async('wshop_downloads', array($o,'wshop_downloads'));
        
        add_filter('wshop_online_post_types', array($o,'wshop_online_post_types'));
       
        add_filter("wshop_order_download_email_received", array($this,'wshop_email_order_received'),10,2);
    }
    
    public function on_after_init(){
        WShop_Download_Field::instance();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see Abstract_WShop_Add_Ons::on_init()
     */
    public function on_init(){
        $o = $this;
         
        $o->setting_uris = array(
            'settings'=>array(
                'title'=>__('Settings',WSHOP),
                'url'=>admin_url('admin.php?page=wshop_page_default&section=menu_default_modal&sub=add_ons_download')
            )
        );
    }
    /**
     * 
     * @param unknown $call
     * @param WShop_Order $order
     * @return WShop_Add_On_Download[]|string[]
     */
    public function wshop_email_order_received($call,$order){
        return array(
            function ($order){
                $user_email = $order->get_email_receiver();
                 
                $settings =  array(
                    '{email:customer}'=>$user_email,
                    '{order_number}'=>$order->id,
                    '{order_date}'=>date('Y-m-d H:i',$order->paid_date)
                );
            
                $content =WShop::instance()->WP->requires(
                    $this->domain_dir,
                    "download/emails/order-received.php",
                    array('order'=>$order)
                );
            
                $email = new WShop_Email('order-received');
                return $email->send($settings,$content);
            }
        );
    }
    
    public function wshop_order_received_url($url,$order){
        $location = isset($order->metas['location'])&&!empty($order->metas['location'])?esc_url_raw($order->metas['location']):null;
        if(!empty($location)){
            return $location;
        }
        
        return $url;
    }
   
   
    public function wshop_downloads($atts = array(),$content=null){
        return WShop_Async::instance()->async_call('wshop_downloads', function(&$atts,&$content){
            if(!is_array($atts)){
                $atts = array();
            }
            
            if(!isset($atts['post_id'])||empty($atts['post_id'])){
                global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
            
            if(!isset($atts['location'])||empty($atts['location'])){
                $atts['location'] =  WShop_Helper_Uri::get_location_uri();
            }
        
        },function(&$atts,&$content){
            $atts['section']='download';
            return WShop::instance()->WP->requires(WShop_Add_On_Download::instance()->domain_dir, 'download/button-purchase.php',array(
                'content'=>$content,
                'atts'=>$atts
            ));
        },
        array(
            'style'=>null,
            'post_id'=>0,
            'roles'=>null,//admin1,admin2  or  all |null
            'class'=>'xh-btn xh-btn-danger xh-btn-sm',
            'location'=>null
        ),
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