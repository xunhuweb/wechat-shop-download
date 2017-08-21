<?php
if (! defined('ABSPATH')) {
    exit();
}

$request = WShop_Async::instance()->shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array(
    'cart_id'=>0
)), stripslashes_deep($_REQUEST));

$params =  WShop_Async::instance()->shortcode_atts(array(
    'notice_str'=>null,
    'action'=>null
), stripslashes_deep($_REQUEST));

if(!WShop::instance()->WP->ajax_validate(array_merge($request,$params), isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
    WShop::instance()->WP->wp_die(WShop_Error::err_code(701),false,false);
    return;
}

$context = $request['context'];
?>

<div class="xh-layout">
<div class="xh-title-h3"><?php echo __('Checkstand',WSHOP)?></div>
	<div class="xh-form">
    	<?php 
    	if(!is_user_logged_in()&&!$request['enable_guest']){
    	    ?>
		    <div id="fields-error" style="display: block;">
    		    <div class="xh-alert xh-alert-danger" role="alert"><?php echo __('Sorry! You need to log in to continue.',WSHOP)?><a href="<?php echo wp_login_url($request['location'])?>"><?php echo __('Login now',WSHOP)?></a></div>
		    </div>
		    <?php 
		}
    			
    	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-shopping-cart.php',array(
    	    'request'=>$request
    	));
    	
    	do_action('wshop_checkout',$context,$request);
    	
    	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-payment-gateways.php',array(
    	    'request'=>$request
    	));
    	
    	do_action('wshop_checkout_step',$context,$request);
    
    	$request = stripslashes_deep($_REQUEST);
    	$request['action'] = 'wshop_checkout';
    	$request['tab'] = 'create_order';
    	
    	echo WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay-btn.php',array(
    	    'request'=>WShop_Async::instance()->shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array(
                    	    'cart_id'=>0,
                	        'action'=>'',
                	        'tab'=>''
                       )), $request),
    	    'content'=>__('Pay Now',WSHOP)
    	));
    	?>
	</div>
</div>
