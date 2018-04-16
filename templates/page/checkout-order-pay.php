<?php
if (! defined('ABSPATH')) {
    exit();
}

$context = WShop_Helper::generate_unique_id();
?>
<style>
.xh-form{border:3px solid #dadada;
        border-radius: 6px;
        -webkit-box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);
        -moz-box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);
        box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);}
</style>
<div class="xh-layout">
	<div class="xh-form">
		<ul class="steps clearfix">
            <li>选择商品</li>
            <li class="active">确认付款</li>
            <li>下单成功</li>
        </ul>
    	<?php
		//购物车
    	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-shopping-cart.php',$context);
    	
    	//购物车扩展  -> 表单 /优惠券等
    	$calls = apply_filters('wshop_checkout_cart',array(),$context);
    	foreach ($calls as $call){
    	    $call($context);
    	}
    	
    	//支付网关
    	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-payment-gateways.php',$context);
    	
    	//其它字段，比如条款等
    	$calls = apply_filters('wshop_checkout_payment_gateways',array(),$context);
        foreach ($calls as $call){
    	    $call($context);
    	}
    	?>
         <div class="block20"></div>       
        	<div class="clearfix xh-checkoutbg" >
            	<span class="xh-total-price xh-pull-left" style="display:none;" id="wshop-<?php echo $context?>-actual-amount"></span>
            	<?php 
            	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-total-amount.php',array(
            	    'context'=>$context
            	));
            	?><script type="text/javascript">
            	(function($){
					$(document).bind('wshop_<?php echo $context?>_show_amount',function(e,view){
	    				var total =view.total_amount;
	    				if(total<=0){
	    					$('#wshop-<?php echo $context?>-actual-amount').html('').hide();
	    				}else{
	    					$('#wshop-<?php echo $context?>-actual-amount').html('<?php echo __('Total:',WSHOP)?>'+view.symbol+total.toFixed(2)).show();
	    				}
	    			});
				})(jQuery);
            	</script>
            	<?php
            	echo WShop::instance()->WP->requires(WSHOP_DIR, '__purchase.php',array(
            	    'content'=>__('Pay Now',WSHOP),
            	    'class'=>'xh-btn xh-btn-warning xh-btn-lg xh-pull-right',
            	    'location'=>WShop_Helper_Uri::get_location_uri(),
            	    'context'=>$context,
            	    'modal'=>'checkout'
            	));
        	?>
    	</div>
	</div>
</div>
