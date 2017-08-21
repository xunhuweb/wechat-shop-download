<?php 
if (! defined('ABSPATH')) {
    exit();
}

$data = WShop_Temp_Helper::get('atts','templates');
$request = $data['request'];
$context = $request['context'];
?>
<div class="block20"></div>
<div class="xh-title-soft clearfix"><?php echo __('Payment method',WSHOP)?></div>
<ul class="pay clearfix">
	<?php 
	$gateways = WShop::instance()->payment->get_payment_gateways();
	$index =0;
	foreach ($gateways as $gateway){
	    ?><li><a data-id="<?php echo esc_attr($gateway->id)?>" class="<?php echo $index++==0?"active":"";?> gateway gateway-<?php echo $context;?>"><img src="<?php echo esc_attr($gateway->icon)?>" alt="<?php echo esc_attr($gateway->title);?>"></a></li><?php 
	}
	?>
</ul>
<script type="text/javascript">
(function($){
    $('.gateway-<?php echo $context;?>').click(function(){
    	$('.gateway-<?php echo $context;?>.active').removeClass('active');
    	$(this).addClass('active');
    });
    
    $(document).bind('wshop_form_<?php echo $context?>_submit',function(e,data){
    	var $gateway = $('.gateway.active');
    	if($gateway.length<=0){
    		$gateway =$('.gateway');
    	}
    
    	if($gateway.length<=0){
    		return;
    	}
    
    	data.payment_method=$gateway.attr('data-id');
    });
})(jQuery);
</script>
<?php 

do_action('wshop_checkout_order_pay_payments',$request);
?>