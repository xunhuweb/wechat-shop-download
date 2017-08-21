<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('wshop_price')){
    function wshop_price($post_ID=0,$symbol = true,$echo = true){
        $product = WShop::instance()->payment->get_current_product($post_ID);
        if(!$product->is_load()){
            ob_start();
            WShop::instance()->WP->wp_die(__('Post is not found!',WSHOP),false,false);
            return ob_get_clean();
        }
        
        $price = $product->get_single_price($symbol);
        
        if($echo) echo $price;return $price;
    }
}

?>