<?php 
if (! defined('ABSPATH')) {
    exit();
}

$context = WShop_Temp_Helper::clear('atts','templates');

$shopping_cart = WShop_Shopping_Cart::get_cart();

if($shopping_cart instanceof WShop_Error){
    WShop::instance()->WP->wp_die($shopping_cart,false,false);
    return;
}

$shopping_cart_items = $shopping_cart->get_items();
?>
<style>
.xh-form .xh-input-group-btn:last-child>.xh-btn, .xh-form .xh-input-group-btn:last-child>.btn-group {
    margin-left: 0px;
}
</style>
<div class="xh-title-soft clearfix "><?php echo __("Product Info",WSHOP)?> <span class="sl"><?php echo __('Qty',WSHOP)?></span></div>
<div class="block20"></div>
<div id="shopping-cart-<?php echo $context;?>">
<?php 
if($shopping_cart_items instanceof WShop_Error){
    ?><div style="color:red;"><?php echo $shopping_cart_items->errmsg;?></div><?php
}else
if(count($shopping_cart_items)>0){
    foreach ($shopping_cart_items as $post_id=>$item){
        $product = $item['product'];
        echo $product->shopping_cart_item_html($shopping_cart,$item['qty'],$context);
    }
}else{
    ?><div>购物车中暂无商品！</div><?php 
}?>
</div>

<?php 
do_action('wshop_checkout_order_pay_shopping_cart',$context);
?>