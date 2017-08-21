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
		    'wshop_page_checkout'=>__CLASS__ . '::wshop_page_checkout'
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
	    if(count($query_vars)==0){
	        ob_start();
	        WShop::instance()->WP-> wp_die(WShop_Error::error_unknow(),false,false);
	        return ob_get_clean();
	    }
	    
	    $keys =array_keys($query_vars);
	    $current_key =$keys[0];
	    foreach ( $wp->query_vars as $key => $value ) {
	        // Ignore pagename param.
	        if ( 'pagename' === $key ||!in_array($key, $query_vars)) {
	            continue;
	        }
	    
	        $current_key = $key;
	    }
	    
	    return apply_filters( "wshop_account_{$current_key}_endpoint", null,$atts,$content);
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
