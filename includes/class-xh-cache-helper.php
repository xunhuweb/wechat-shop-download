<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WShop_Cache_Helper class.
 *
 * @class 		WShop_Cache_Helper
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WShop_Cache_Helper {
	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 * @param  string $group
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key _cache_prefix to invalidate when needed
		$prefix = wp_cache_get( 'wshop_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'wshop_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'wshop_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 * @param  string $group
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'wshop_' . $group . '_cache_prefix', 1, $group );
	}
}

/**
 * 临时缓存
 * @author rain
 * @since 1.0.0
 */
class WShop_Temp_Helper{
    private static $_data=array();
    
    public static function get($key,$group='common',$_default=null){
        return isset(self::$_data[$group][$key])
        ?self::$_data[$group][$key]
        :$_default;
    }
    public static function clear($key,$group='common',$_default=null){
        if( isset(self::$_data[$group][$key])){
            $data =self::$_data[$group][$key];
            self::$_data[$group][$key]=$_default;
            return $data;
        }
       return $_default;
    }
    public static function set($key,$val,$group='common'){
        self::$_data[$group][$key]=$val;
    }
}