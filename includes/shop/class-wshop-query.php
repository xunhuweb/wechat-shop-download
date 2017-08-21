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
    
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * Constructor for the query class. Hooks in methods.
     *
     * @access public
     */
    private function __construct() {
        add_action( 'wshop_flush_rewrite_rules', array( $this, 'init_query_vars' ));
        add_action( 'wshop_flush_rewrite_rules', array( $this, 'add_endpoints' ));
        
        add_action( 'init', array( $this, 'init_query_vars' ) );
        add_action( 'init', array( $this, 'add_endpoints' ) );
        add_filter('the_title', array( $this, 'the_title' ) ,10,2);
        if ( ! is_admin() ) {
            add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
            add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
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
    
    public function the_title($title,$post_ID){
        global $wp_query;
       
        if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && is_page()) {
            $endpoint = $this->get_current_endpoint();
        
            if(empty($endpoint)){
                return $title;
            }
        
            switch ($endpoint){
                case 'order-pay':
                    return get_option('blogname')." - ".__('Checkout',WSHOP);
                case 'order-received':
                    return get_option('blogname')." - ".__('Order details',WSHOP);
                default:
                    return apply_filters('wshop_checkout_endpoint_title', $title,$endpoint,$post_ID);
            }
        }
        
        return $title;
    }
    
    /**
     * Init query vars by loading options.
     */
    public function init_query_vars() {
        $options =WShop_Settings_Checkout_Options::instance();
        // Query vars to add to WP.
        $this->query_vars =apply_filters( 'wshop_get_query_vars', array(
            // Checkout actions.
            'order-pay'          => $options->get_option('endpoint_order_pay','order-pay'),
            'order-received'     => $options->get_option('endpoint_order_received','order-received'),
        ));
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
            }
        }
    }
    
    /**
     * Get query vars.
     *
     * @return array
     */
    public function get_query_vars() {
        return  $this->query_vars ;
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
            $vars[] = $key;
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