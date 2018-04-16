<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('wshop_price')){
    function wshop_price($atts=array(),$echo = true){     
        if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
            global $wp_query;
            $default_post=$wp_query->post;
            $atts['post_id']=$default_post?$default_post->ID:0;
        }
        
        $atts = shortcode_atts(apply_filters('wshop_price_atts',array(
	        'post_id'=>0,//post ID
	        'symbol'=>1 //0|1 是否带货币符号
	    )), $atts);
        
        $post_ID = $atts['post_id'];
        $symbol= $atts['symbol'];
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


if(!function_exists('wshop_btn_add_to_cart')){
    function wshop_btn_add_to_cart($attr=array(),$content,$echo = true){
        return WShop_Async::instance()->async_call('wshop_btn_add_to_cart', function(&$atts,&$content){
            if(!isset($atts['location'])||empty($atts['location'])){
                $atts['location'] =  WShop_Helper_Uri::get_location_uri();
            }
        
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
        
        },function(&$atts,&$content){
            $content=empty($content)?__('Add to Shopping cart',WSHOP):$content;
        
            return WShop::instance()->WP->requires(WSHOP_DIR, 'button-add-to-cart.php',array(
                'content'=>$content,
                'atts'=>$atts
            ));
        },
        array(
            'location'=>null,
            'post_id'=>null,
            'class'=>'xh-btn xh-btn-danger xh-btn-lg',
            'style'=>null
        ),
        $atts,
        $content);
    }
}
?>