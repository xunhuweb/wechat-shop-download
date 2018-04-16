<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WShop_Query{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;
    public $query_vars=array();
    public $endpoint_settings=array();
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    public $pages = array();
   
    /**
     * Constructor for the query class. Hooks in methods.
     *
     * @access public
     */
    private function __construct() {
        add_action( 'wshop_flush_rewrite_rules', array( $this, 'init_query_vars' ) ,11);
        add_action( 'wshop_flush_rewrite_rules', array( $this, 'add_endpoints' ) ,11);
        
        add_filter('wshop_checkout_options_3', array($this,'wshop_checkout_options_3'),10,1);
        
        add_action( 'init', array( $this, 'init_query_vars' ) ,10);
        add_action( 'init', array( $this, 'add_endpoints' ) ,11);
        
        //4.4.0 current_theme_supports( 'title-tag' ) 
        add_filter('document_title_parts', array( $this, 'document_title_parts' ) ,10,1);
        //4.4.0前老版本
        add_filter('wp_title_parts', array($this,'document_title_parts'),10,1);
        
        if ( ! is_admin() ) {
            add_filter( 'query_vars', array( $this, 'add_query_vars' ), 11 );
            add_action( 'parse_request', array( $this, 'parse_request' ), 11 );
        }
        
        $this->register_page('checkout', array(
            'title'=>__('Checkout page',WSHOP),
            'description'=>__('Checkout pages contains shipping address or other custom fields.',WSHOP)
        ));
        
        $this->register_endpoint('checkout','order-pay', array(
            'title'=>__('Checkout',WSHOP),
            'page_title'=>__('Checkout',WSHOP),
            'description'=>__('Endpoint for the "Checkout &rarr; Pay" page.',WSHOP)
        ),array('WShop_Hooks','account_order_pay'));
        
        $this->register_endpoint('checkout','order-received', array(
            'title'=>__('Order received',WSHOP),
            'page_title'=>__('Order details',WSHOP),
            'description'=>__('Endpoint for the "Checkout &rarr; Order received" page.',WSHOP)
        ),array('WShop_Hooks','account_order_received'));
    }
    
    public function wshop_checkout_options_3($settings){
        foreach ($this->pages as $page=>$page_setting){
            $page_setting =  shortcode_atts(array(
                'title'=>$page,
                'description'=>null,
                'type'=>'select',
                'func'=>true,
                'options'=>array($this,'get_page_options')
            ), $page_setting);
            
            $settings["title_{$page}"] =array(
                'title'=>$page_setting['title'].' - '.__('Endpoints',WSHOP),
                'type'=>'subtitle',
                'description'=>__('Endpoints are appended to your page URLs to handle specific actions during the checkout process. They should be unique.',WSHOP)
            );
            
            $settings["page_{$page}"]=$page_setting;
            
            if(isset($this->endpoint_settings[$page])){
                foreach ($this->endpoint_settings[$page] as $endpoint=>$endpoint_setting){
                    $settings["endpoint_{$page}_{$endpoint}"]=$endpoint_setting;
                }
            }
        }
        
        return $settings;
    }
    
    public function get_page_options(){
        global $wpdb;
        $pages =$wpdb->get_results(
            "select ID,post_title
            from {$wpdb->prefix}posts
            where post_type='page'
            and post_status='publish';");
        $options = array(
            '0'=>__('Select...',WSHOP)
        );
        if($pages){
            foreach ($pages as $page){
                $options[$page->ID]=$page->post_title;
            }
        }
    
        return $options;
    }
    
    public function register_page($page,$setting){
        $this->pages[$page] = $setting;
    }
    
    public function get_registered_endpoints($page){
        return isset($this->endpoint_settings[$page])?$this->endpoint_settings[$page]:array();
    }
    
    public function register_endpoint($page,$endpoint,$settings,$callback=null){
        $settings = shortcode_atts(array(
            'title'=>$endpoint,
            'page_title'=>null,
            'description'=>null,
            'type'=>'text',
            'default'=>$endpoint
        ), $settings);

        if(empty($settings['page_title'])){
            $settings['page_title'] = $settings['title'];
        }
        
        $this->endpoint_settings[$page][$endpoint]=$settings;
        
        if($callback){
            add_filter("wshop_endpoint_{$page}_{$endpoint}",$callback,10,2);
        }
    }
  
    public function get_current_endpoint() {
        global $wp;
        foreach ( $this->get_query_vars() as $key => $value ) {
            if ( isset( $wp->query_vars[ $key ] ) ) {
                return $key;
            }
        }
        return '';
    }
 
 /**
     * @param array $title
     * @return array
     */
    public function document_title_parts($title){
        if(!is_page()){
            return $title;
        }
       
        $endpoint = $this->get_current_endpoint();
        if(!$endpoint){
            return $title;
        }
        
        foreach ($this->pages as $page=>$setting){
            if(!isset($setting['default'])|| !is_page($setting['default'])){
                continue;
            }
            if(isset($this->endpoint_settings[$page][$endpoint])){
                if(!$title||!is_array($title)){
                    $title = array();
                }
                
                $new_title =array(
                    'endpoint'=> apply_filters('xh_uc_endpoint_title', $this->endpoint_settings[$page][$endpoint]['page_title'],$endpoint,$page)
                );
                
                return array_merge($new_title,$title);
            }
        }
        
        return $title;
    }
    
    /**
     * Init query vars by loading options.
     */
    public function init_query_vars() {
        $options =WShop_Settings_Checkout_Options::instance();
    
        foreach ($this->pages as $page=>$settings){
            $this->pages[$page]['default'] = $options->get_option("page_$page",null);
        }
        
        foreach ($this->endpoint_settings as $page=>$endpoints){
            foreach ($endpoints as $endpoint=>$setting){
                $this->query_vars[$endpoint] = $options->get_option("endpoint_{$page}_$endpoint",$endpoint);
            }
        }
    }
    
    public function get_endpoint_url($page,$endpoint=null,$params = array()){
        $api =WShop_Settings_Checkout_Options::instance();
        $page_id =  $api->get_option("page_{$page}");
        $permalink = get_page_link($page_id);
        
        if(!empty($endpoint)){
            $endpoint = $api->get_option("endpoint_{$page}_$endpoint");

            if ( get_option( 'permalink_structure' ) ) {
                if ( strstr( $permalink, '?' ) ) {
                    $query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
                    $permalink    = current( explode( '?', $permalink ) );
                } else {
                    $query_string = '';
                }
                $permalink =  trailingslashit( $permalink ) . $endpoint  . $query_string;
            } else {
                $permalink .= (strpos($permalink, '?')===false?'?':'&')."{$endpoint}=".($params&&is_array($params)&&isset($params[$endpoint])?$params[$endpoint]:'');
            }
        }
       
    
        if(count($params)>0){
            $permalink.=(strpos($permalink, '?')===false?'?':'&').http_build_query($params);
        }
    
        return $permalink;
    }
    
    /**
     * Parse the request and look for query vars - endpoints may not be supported.
     */
    public function parse_request() {
        global $wp;
    
        // Map query vars to their keys, or get them if endpoints are not supported
        foreach ( $this->get_query_vars() as $key => $var ) {
            if ( isset( $_GET[ $var ] ) ) {
                $wp->query_vars[ $key ] = $_GET[ $var ];
            } elseif ( isset( $wp->query_vars[ $var ] ) ) {
                $wp->query_vars[ $key ] = $wp->query_vars[ $var ];
                
                if($var!=$key){
                    unset($wp->query_vars[ $var ]);
                }
            }
        }
        
    }
    
    /**
     * Get query vars.
     *
     * @return array
     */
    public function get_query_vars() {
        return $this->query_vars ;
    }
    
    /**
     * Add query vars.
     *
     * @access public
     * @param array $vars
     * @return array
     */
    public function add_query_vars( $vars ) {
        foreach ( $this->get_query_vars() as $key => $var ) {
            if(!empty($var)){
                $vars[] = $var;
            }
        }
        return $vars;
    }
    
    /**
     * Add endpoints for query vars.
     */
    public function add_endpoints() {
        $mask = $this->get_endpoints_mask();
    
        foreach ( $this->get_query_vars() as $key => $var ) {
            if ( ! empty( $var ) ) {
                add_rewrite_endpoint( $var, $mask );
            }
        }
    }
    
    /**
    * Endpoint mask describing the places the endpoint should be added.
    *
    * @since 2.6.2
    * @return int
    */
    public function get_endpoints_mask() {
        return EP_PAGES;
    }
}

?>