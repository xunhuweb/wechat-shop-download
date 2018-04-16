<?php

/*
 * Plugin Name: Wechat Shop
 * Plugin URI: https://www.wpweixin.net/product/1376.html
 * Description: 一款适合中国人，功能强大的微信支付插件，支持付费下载，表单支付，付费阅读，会员下载，会员浏览，支持卡密销售，支持定义的表单，支持微信和支付宝。
 * Author: 迅虎网络
 * Version: 1.0.3
 * Author URI:  http://www.wpweixin.net
 * Text Domain: wshop
 * Domain Path: /lang
 */

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
	
if(defined('WP_DEBUG')&&WP_DEBUG===true){
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}	

if ( ! class_exists( 'WShop' ) ) :
final class WShop {
    /**
     * Social version.
     *
     * @since 1.0.0
     * @var string
     */
    public $version = '1.0.3';
    
    /**
     * 最小wp版本
     * @var string
     */
    public $min_wp_version='3.7';
    
    /**
     * License ID
     * 
     * @var string
     */
    public static $license_id=array(
        'wechat_shop',
        'wechat_shop_download',
        'wechat_shop_from',
        'wechat_shop_cdkey',
        'wechat_shop_memberdownload',
        'wechat_shop_pay_per_view'
    );
    
    /**
     *
     * @var string[]
     */
    public $plugins_dir =array();
  
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
     * 已安装的插件（包含激活的，可能包含未激活的）
     * is_active 标记是否已被激活
     * 
     * 一般请求：只加载被激活的插件，
     * 在调用 WShop_WP_Api::get_plugin_list_from_system后，加载所有已安装的插件
     * @var Abstract_WShop_Add_Ons[]
     */
    public $plugins=array();
    
    /**
     * session
     * 缓存到自定义数据库中
     * 
     * @var XH_Session_Handler
     */
    public $session;
    
    /**
     * 登录接口
     * @var WShop_Payment_Api
     */
    public $payment;
   
    /**
     * wordpress接口
     * @var WShop_WP_Api
     */
    public $WP;

    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return WShop - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     * 
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WSHOP ), '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * 
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WSHOP ), '1.0.0' );
    }

    public function supported_wp_version(){
        global $wp_version;
        return version_compare( $wp_version, $this->min_wp_version, '>=' );
    }
    
    /**
     * Constructor.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
    
        $this->includes();  
        $this->init_hooks();
        WShop_install::instance();
        do_action( 'wshop_loaded' );
    }

    /**
     * Hook into actions and filters.
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        load_plugin_textdomain( WSHOP, false,dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
        
        $this->include_plugins();
        WShop_Query::instance();
        
        add_action( 'init', array( $this,                   'init' ), 1 );
        add_action( 'init', array( $this,                   'after_init' ), 999 );
        add_action('import_end', array($this,'import_end'),10);
        add_action( 'init', array( 'WShop_Page',            'init' ), 9 );
        add_action( 'init', array( 'WShop_Shortcodes',      'init' ), 10 );
        add_action( 'init', array( 'WShop_Ajax',            'init' ), 10 );
        add_action('after_setup_theme', array($this,        'after_setup_theme'),10);
        WShop_Hooks::init();
        add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'),99);
        add_action('wp_enqueue_scripts', array($this,'wp_enqueue_scripts'),99);
        WShop_Log::instance( new WShop_Log_File_Handler ( WSHOP_DIR . "/logs/" . date ( 'Y/m/d' ) . '.log' ));
        register_activation_hook ( WSHOP_FILE, array($this,'_register_activation_hook'),10 );
        register_deactivation_hook(WSHOP_FILE,  array($this,'_register_deactivation_hook'),10);        
        add_action ( 'plugin_action_links_'. plugin_basename( WSHOP_FILE ),array($this,'_plugin_action_links'),10,1);
    }

    public function after_setup_theme(){
        global $pagenow;
        // Load the functions for the active theme, for both parent and child theme if applicable.
        if ( ! wp_installing() || 'wp-activate.php' === $pagenow ) {
            if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/wechat-shop/functions.php' ) ){
                include( STYLESHEETPATH . '/wechat-shop/functions.php' );
            }
    
            if ( file_exists( TEMPLATEPATH . '/wechat-shop/functions.php' ) ){
                include( TEMPLATEPATH . '/wechat-shop/functions.php' );
            }
        }
    }
    
    public function after_init(){
        WShop_Product_Fields::instance();
        do_action('wshop_after_init');
    }
    
    /**
     * 获取已激活的扩展
     * @param string $add_on_id
     * @return Abstract_WShop_Add_Ons|NULL
     * @since 1.0.0
     */
    public function get_available_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id&&$plugin->is_active){
                return $plugin;
            }
        }
        
        return null;
    }
    /**
     * 获取已激活的扩展
     * @return Abstract_WShop_Add_Ons[]
     * @since 1.0.0
     */
    public function get_available_addons(){
        $results = array();
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->is_active){
                $results[]= $plugin;
            }
        }
    
        return $results;
    }
    
    public function import_end(){
        //wordpress 导入数据时，更新附加信息
        global $wpdb;
        $wpdb->query(
            "insert into {$wpdb->prefix}wshop_product(
              post_ID,
              sale_price
            )
            
            select p.post_id,
                   p.meta_value
            from (
               select p.post_id,p.meta_value
            	from {$wpdb->prefix}postmeta p
            	left join {$wpdb->prefix}wshop_product wp on wp.post_ID = p.post_id
            	where p.meta_key='wshop_sale_price'
            			and wp.post_ID is null
            	group by p.post_id
            )p");
        
        if(!empty($wpdb->last_error)){
            WShop_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
    
    /**
     * 获取已安装的扩展
     * @param string $add_on_id
     * @return Abstract_WShop_Add_Ons|NULL
     * @since 1.1.7
     */
    public function get_installed_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id){
                return $plugin;
            }
        }
    
        return null;
    } 
    /**
     * 加载扩展
     * @since 1.0.0
     */
    private function include_plugins(){
        $installed = get_option('wshop_plugins_installed',array());
        if(!$installed){
            return;
        }
        $dirty=false;
        foreach ($installed as $file){
            $file = str_replace('\\', '/', $file);
            $valid = false;
            foreach ($this->plugins_dir as $dir){
                if(strpos($file, $dir)===0){
                    $valid=true;
                    break;
                }
            }
            if(!$valid){
                continue;
            }
            
            $add_on=null;
            if(isset($this->plugins[$file])){
                $add_on=$this->plugins[$file];
            }else{
                if(file_exists($file)){
                    $add_on = require_once $file;
                    if($add_on&&$add_on instanceof Abstract_WShop_Add_Ons){
                        $this->plugins[$file]=$add_on;
                    }else{
        	            $add_on=null;
        	        }
                }else{
                    unset($installed[$file]);
                    $dirty =true;
                }
            }
            
            if($add_on){
                $this->__load_plugin($add_on);
            }
            
            if($dirty){
                update_option('wshop_plugins_installed', $installed,true);
            }
        }
    }
    
    /**
     * 当前api为内部api，轻忽随意使用
     * @param Abstract_WShop_Add_Ons $add_on
     * @since 1.0.3
     */
    public function __load_plugin($add_on){
        $add_on->is_active=true;
        //初始化插件
        $add_on->on_load();
        //监听init
        add_action('init', array($add_on,'on_init'),10);
        add_action('wshop_after_init', array($add_on,'on_after_init'),10);
        add_filter('wshop_shortcodes', array($add_on,'add_shortcodes'),10,1);    
        add_action('init', array($add_on,'register_post_types'),10);
        add_action('wshop_flush_rewrite_rules', array($add_on,'register_post_types'),10);
        add_action('wshop_cron', array($add_on,'on_cron'),10);
        add_action('wshop_after_init', array($add_on,'register_fields'),10);
    }
    
    /**
     * ajax url
     * @param string|array $action
     * @param bool $hash
     * @return string
     * @since 1.0.0
     */
    public function ajax_url($action=null,$hash = false,$notice=false,$_params = array()) {   
        $ps =array();
        $url = WShop_Helper_Uri::get_uri_without_params(admin_url( 'admin-ajax.php' ),$ps);
        $params = array();
        
        if($action){
            if(is_string($action)){
                $params['action']=$action;
            }else if(is_array($action)){
                $params=$action;
            }
        }
        
        if(isset($params['action'])&&!empty($params['action'])){
            if($notice){
                $params[$params['action']] = wp_create_nonce($params['action']);
            }
        }
        
        if($hash){
            $params['notice_str'] = str_shuffle(time());
            $params['hash'] = WShop_Helper::generate_hash($params, $this->get_hash_key());
        }
        
        if(count($_params)>0){
           foreach ($_params as $k=>$v){
               $params[$k]=$v;
           } 
        }
        if(count($params)>0){
            $url.="?".http_build_query($params);
        }
        return $url;
    }
    
    /**
     * 生成请求
     * @param array $request
     * @return array
     */
    public function generate_request_params($request,$notice_key=null){
        if(!empty($notice_key)){
            $request[$notice_key] = wp_create_nonce($notice_key);
        }
       
        $request['notice_str'] = str_shuffle(time());
        $request['hash'] = WShop_Helper::generate_hash($request, $this->get_hash_key());  
    
        
        return $request;
    }
    
    /**
     * 获取加密参数
     * @return string
     * @since 1.0.0
     */
    public function get_hash_key(){
        $hash_key = AUTH_KEY;
        if(empty($hash_key)){
            $hash_key = WSHOP_FILE;
        }
        
        return $hash_key;
    }
    
    /**
     * 插件初始化
     * 
     * 在ini 之前已启用
     * 初始化需要的数据库，初始化资源等
     * @since 1.0.0
     */
    public function _register_activation_hook(){
        //第一次安装，所有插件自动安装
        $plugins_installed =get_option('wshop_plugins_installed',null);
        if(!is_array($plugins_installed)||count($plugins_installed)==0){
            wp_cache_delete('wshop_plugins_installed','options');
            update_option('wshop_plugins_installed', array(
                WSHOP_DIR.'/add-ons/wpopen-alipay/init.php',
                WSHOP_DIR.'/add-ons/wpopen-wechat/init.php'
            ),true);
           
            $this->include_plugins();
            unset($plugins_installed);
            
            //默认启用alipay wechat
            if(class_exists('WShop_Payment_Gateway_Wpopen_Alipay')){
                WShop_Payment_Gateway_Wpopen_Alipay::instance()->update_option('enabled', 'yes');
            }
            
            if(class_exists('WShop_Payment_Gateway_Wpopen_Wechat')){
                WShop_Payment_Gateway_Wpopen_Wechat::instance()->update_option('enabled', 'yes');
            }
        }
        
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_install();
        }
        
        //数据表初始化
        $session_db =new XH_Session_Handler_Model();
        $session_db->init();
        
        $order_db = new WShop_Order_Model();
        $order_db->init();
        
        $order_item_db = new WShop_Order_Item_Model();
        $order_item_db->init();
        
        $productdb= new WShop_Product_Model();
        $productdb->init();
        
        WShop_Page::init_page();
        
        $email_db = new WShop_Email_Model();
        $email_db->init();
        
        WShop_Hooks::check_add_ons_update();
       
        do_action('wshop_register_activation_hook');
        
        ini_set('memory_limit','128M');
        
        do_action('wshop_flush_rewrite_rules');
        flush_rewrite_rules(); 
    }
    
    public function _register_deactivation_hook(){
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_uninstall();
        }
        do_action('wshop_register_deactivation_hook');
    }
    
       
    /**
     * 定义插件列表，设置菜单键
     * @param array $links
     * @return array
     * @since 1.0.0
     */
    public function _plugin_action_links($links){
        if(!is_array($links)){$links=array();}
        $install =WShop_Install::instance();
        if($install->is_plugin_installed()){
            return array_merge ( array (
                'settings' => '<a href="' . $this->WP->get_plugin_settings_url().'">'.__('Settings').'</a>',
                'license'=>'<a href="' . $install->get_plugin_install_url().'">'.__('Rebuild',WSHOP).'</a>',
            ), $links );
        }else{
            return array_merge ( array (
                'setup'=>'<a href="' . $install->get_plugin_install_url().'">'.__('Setup',WSHOP).'</a>'
            ), $links );
        }
    }
    
    /**
     * Define Constants.
     * @since 1.0.0
     */
    private function define_constants() {
        self::define( 'WSHOP', 'wshop' );
        self::define( 'WSHOP_FILE', __FILE__ );
        
        require_once 'includes/class-xh-helper.php';
        self::define( 'WSHOP_DIR', WShop_Helper_Uri::wp_dir(__FILE__));
        self::define( 'WSHOP_URL', WShop_Helper_Uri::wp_url(__FILE__) );
        
        $content_dir = WP_CONTENT_DIR;
        $this->plugins_dir=array(
            str_replace('\\', '/', $content_dir).'/wechat-shop/add-ons/',
            WSHOP_DIR.'/add-ons/',
        );
    }

    /**
     * Define constant if not already set.
     *
     * @since 1.0.0
     * @param  string $name
     * @param  string|bool $value
     */
    public static function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * What type of request is this?
     * 
     * @since 1.0.0
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public static function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     * @since  1.0.0
     */
    private function includes() {
        require_once 'includes/error/class-xh-error.php';
        require_once 'includes/logger/class-xh-log.php';
        
        require_once 'includes/class-xh-cache-helper.php';
        include_once 'includes/abstracts/abstract-xh-schema.php';
        
        if(!class_exists('Abstract_XH_Session')){
            require_once 'includes/class-xh-session-handler.php';
        }

        require_once 'includes/abstracts/abstract-xh-settings.php';
        require_once 'includes/abstracts/abstract-xh-add-ons.php';
        
        require_once 'includes/abstracts/abstract-xh-object.php';
        require_once 'includes/abstracts/abstract-xh-fields.php';
        
        require_once 'includes/abstracts/abstract-xh-payment-gateway.php';
        require_once 'includes/abstracts/abstract-wshop-product.php';
        require_once 'includes/abstracts/abstract-wshop-order.php';
        require_once 'includes/abstracts/abstract-wshop-shopping-cart.php';
        require_once 'includes/abstracts/abstract-wshop-order-item.php';
    
        require_once 'install/class-xh-install.php';
        require_once 'includes/admin/class-wshop-admin.php';
        require_once 'includes/admin/abstracts/abstract-xh-view-form.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-menu.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-page.php';
        
        require_once 'includes/shop/class-wshop-async.php';
        require_once 'includes/shop/class-wshop-hooks.php';
        require_once 'includes/shop/class-wshop-query.php';
        require_once 'includes/shop/class-wshop-page.php';
        require_once 'includes/shop/class-wshop-shortcodes-functions.php';
        require_once 'includes/shop/class-wshop-shortcodes.php';
        require_once 'includes/shop/class-wshop-ajax.php';
     
        require_once 'includes/shop/class-wshop-payment-api.php';
        require_once 'includes/shop/class-wshop-settings-basic-default.php';
        require_once 'includes/shop/class-wshop-settings-checkout-options.php';
        require_once 'includes/shop/class-wshop-currency.php';
        require_once 'includes/shop/class-wshop-email.php';
        require_once 'includes/shop/class-wshop-shopping-cart.php';
        require_once 'includes/shop/class-wshop-order.php';
        require_once 'includes/shop/class-wshop-product.php';
    }

    /**
     * Init shop when WordPress Initialises.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Before init action.
        do_action( 'wshop_init_before' );
        
        $this->session =XH_Session_Handler::instance();
        $this->payment = WShop_Payment_Api::instance();
        $this->WP = WShop_WP_Api::instance();
        
        if(self::is_request( 'admin' )){
            //初始化 管理页面
            WShop_Admin::instance();
        }
        
        // Init action.
        do_action( 'wshop_init' );
    }
    
    public function on_update($version){
        do_action('wshop_on_update',$version);
    
        WShop_Hooks::check_add_ons_update();
    }
    /**
     * admin secripts
     *
     * @since 1.0.0
     */
    public function admin_enqueue_scripts(){
       $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
       wp_enqueue_script('jquery');
       wp_enqueue_script('media-upload');
       add_thickbox();
       wp_enqueue_media();
       
       wp_enqueue_script('jquery-loading',WSHOP_URL."/assets/js/jquery-loading$min.js",array('jquery'),$this->version,true);
       wp_enqueue_script('WdatePicker',WSHOP_URL."/assets/My97DatePicker/WdatePicker.js",array(),$this->version,true);
       wp_enqueue_script('select2',WSHOP_URL."/assets/select2/select2.full$min.js",array('jquery'),$this->version,true);
       wp_enqueue_script('jquery-tiptip', WSHOP_URL . "/assets/jquery-tiptip/jquery.tipTip$min.js", array( 'jquery' ), $this->version ,true);       
       wp_enqueue_script('wshop-admin',WSHOP_URL."/assets/js/admin.js",array('jquery','select2','jquery-tiptip'),$this->version,true);
    
       wp_localize_script( 'wshop-admin', 'wshop_enhanced_select', array(
           'i18n_no_matches'           => __( 'No matches found', WSHOP ),
           'i18n_ajax_error'           => __( 'Loading failed', WSHOP ),
           'i18n_input_too_short_1'    => __( 'Please enter 1 or more characters', WSHOP ),
           'i18n_input_too_short_n'    => __( 'Please enter %qty% or more characters', WSHOP ),
           'i18n_input_too_long_1'     => __( 'Please delete 1 character', WSHOP ),
           'i18n_input_too_long_n'     => __( 'Please delete %qty% characters', WSHOP ),
           'i18n_selection_too_long_1' => __( 'You can only select 1 item', WSHOP ),
           'i18n_selection_too_long_n' => __( 'You can only select %qty% items', WSHOP ),
           'i18n_load_more'            => __( 'Loading more results&hellip;', WSHOP ),
           'i18n_searching'            => __( 'Loading...', WSHOP ),
           'ajax_url'=>$this->ajax_url(array(
               'action'=>'wshop_obj_search'
           ),true,true)
       ));
       
       wp_enqueue_style('jquery-tiptip', WSHOP_URL . "/assets/jquery-tiptip/tipTip$min.css", array( ), $this->version );
       wp_enqueue_style('jquery-loading',WSHOP_URL."/assets/css/jquery.loading$min.css",array(),$this->version);
       wp_enqueue_style('wshop-admin',WSHOP_URL."/assets/css/admin$min.css",array(),$this->version);
       
       do_action('wshop_admin_enqueue_scripts');
    }
   
   /**
    * front secripts
    *
    * @since 1.0.0
    */
    public function wp_enqueue_scripts(){
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script('jquery');
        
        wp_enqueue_script('wshop',WSHOP_URL."/assets/js/wshop.js",array(/*'jquery'*/),$this->version,true);
        foreach ($this->get_js_params() as $key=>$datas){
            wp_localize_script( 'wshop', $key, $datas);
        }
        
        wp_enqueue_style('jquery-loading',WSHOP_URL."/assets/css/jquery.loading$min.css",array(),$this->version);
        wp_enqueue_script('jquery-loading',WSHOP_URL."/assets/js/jquery-loading$min.js",array(/*'jquery'*/),$this->version,true);
        wp_enqueue_style('wshop',WSHOP_URL."/assets/css/wshop.css",array(),$this->version);
        do_action('wshop_wp_enqueue_scripts');
    }
    
    public function get_js_params(){
        $payment_methods = WShop::instance()->payment->get_payment_gateways();
        $payment_method_array=array();
        if($payment_methods){
            foreach ($payment_methods as $payment_method){
                $payment_method_array[]=array(
                    'id'=>$payment_method->id,
                    'title'=>$payment_method->title,
                    'icon'=>esc_url_raw($payment_method->icon_small)
                );
            }
        }
        
        return array(
            'wshop_jsapi_params'=>array(
                'ajax_url'=>$this->ajax_url(),
                'ajax_url_pay'=>$this->ajax_url(array(
                    'action'=>'wshop_checkout_v2',
                    'tab'=>'pay'
                ),true,true),
                'wp_login_url'=>wp_login_url('#location#'),
                'payment_methods'=>$payment_method_array,
                'msg_no_payment_method'=>__('No payment method found!',WSHOP),
                'msg_err_500'=>WShop_Error::err_code(500)->errmsg,
                'msg_processing'=>__('Processing...',WSHOP),
                'msg_add_to_cart_successfully'=>__('Added successfully!',WSHOP)
            )
        ) ;
    }
}

endif;

WShop::instance();

