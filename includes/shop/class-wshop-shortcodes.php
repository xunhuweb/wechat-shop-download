<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WShop_Shortcodes class
 *
 * @category    Class
 */
class WShop_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    'wshop_page_checkout'=>__CLASS__ . '::wshop_page_checkout',
		    'wshop_account_my_orders'=> "WShop_Hooks::wshop_account_my_orders"
		);
		
		$shortcodes =apply_filters('wshop_shortcodes', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "wshop_shortcode_{$shortcode}", $shortcode ), $function );
		}
	}
	
	
	/**
	 *
	 * @param array $atts
	 * @param string $content
	 * @since 1.0.0
	 */
	public static function wshop_page_checkout($atts = array(),$content=null){
	    global $wp;
	    
	    $query_vars =WShop_Query::instance()->get_query_vars();	  
	    foreach ( $wp->query_vars as $key => $value ) {
	        if(isset($query_vars[$key])){
	            return apply_filters( "wshop_endpoint_checkout_{$key}",$atts,$content);
	        }
	    }
	   
	    ob_start();
	    WShop::instance()->WP->wp_die(WShop_Error::err_code(404),false,false);
	    return ob_get_clean();
	}
	
	/**
	 * @since 1.0.0
	 * @param array $atts
	 * @param string $property
	 */
	public static function get_attr($atts,$property){
	    if($atts){
	        foreach ($atts as $key=>$val){
	            if(strcasecmp($key, $property)===0){
	                return $val;
	            }
	        }
	    }
	    
	    return null;
	}
}
