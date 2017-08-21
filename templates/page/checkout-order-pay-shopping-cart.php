<?php 
if (! defined('ABSPATH')) {
    exit();
}

$data = WShop_Temp_Helper::get('atts','templates');
$request = $data['request'];

$shopping_cart = new WShop_Shopping_Cart($request['cart_id']);
if(!$shopping_cart->is_load()){
    WShop::instance()->WP->wp_die(__('Shoppint cart is empty!',WSHOP),false,false);
    return;
}

$shopping_cart_items = $shopping_cart->get_items();
foreach ($shopping_cart_items as $post_id=>$item){
    $product = $item['product'];
    if(!$product->is_load()){
        continue;
    }
    	
    echo $product->shopping_cart_item_html($shopping_cart,$item['qty'],$request);
}

do_action('wshop_checkout_order_pay_shopping_cart',$request);
?>