<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::get('atts','templates');
$context = $data['request']['context'];
$qty = intval($data['qty']);
$product = $data['product'];
if(!$product instanceof Abstract_WShop_Product){
    return;
}
?>
<div class="xh-title-soft clearfix"><?php echo __("Product Info",WSHOP)?></div>
<div class="block20"></div>

<dl class="xh-prolist clearfix">
    <dt><a target="_blank" href="<?php echo $product->get_link()?>"><img src="<?php echo $product->get_img()?>" alt="<?php echo esc_attr($product->get_title())?>"></a></dt> 
    <dd>
        <div class="ptitle"><?php echo $product->get_title()?></div>
        <p class="price"><?php echo $product->get_single_price(true)?></p>
    </dd>
    <div class="j-item"> <span class="item-num" style="display: block;">×<?php echo $qty;?></span></div>
</dl>
<script type="text/javascript">
	(function($){
		$(document).bind('wshop_<?php echo $context;?>_init_amount_before',function(e,m){
			m.total_amount+=<?php echo round($product->get_single_price(false)*$qty,2)?>;
		});
	})(jQuery);
</script>