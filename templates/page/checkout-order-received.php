<?php
if (! defined('ABSPATH')) {
    exit();
}
$data = WShop_Temp_Helper::get('atts','templates');
$order = isset($data['order'])?$data['order']:null;
if(!$order||!$order instanceof WShop_Order){
    WShop::instance()->WP->wp_die(WShop_Error::err_code(404),false,false);
    return;
}

?>
<div class="xh-layout">
	<?php if($order->is_paid()){
	    ?>
	     <div class="xh-title-h3 clearfix" style="text-align: center;">  <span ><?php echo __('Order received',WSHOP)?></span> </div>
	    <?php 
	}else{
	    ?>
	    <div class="xh-title-h3 clearfix" style="text-align: center;">  <span ><?php echo __('Waitting for payment!',WSHOP)?></span> </div>
	    <?php
	}?>

	<div class="xh-form">
	<?php if($order->is_paid()){
	      ?><div class="xh-title"><?php echo __('Order received',WSHOP)?></div>
	      <div class="block20"></div>
    	    <div  class="clearfix"><?php echo __('Thanks,we have received your order.',WSHOP)?></div><?php 
    	}else{
    	    ?><div class="xh-title clearfix"><?php echo __('Waitting for payment',WSHOP); 
    	       if($order->can_pay()){
    	           ?> <a class="xh-pull-right xh-btn xh-btn-sm xh-btn-primary" href="<?php echo $order->get_pay_url()?>"><?php echo __('Pay',WSHOP)?></a>
    	           <?php
    	       }?></div>
    	    <div class="block20"></div>
    	    <div class="clearfix">
    	    	<?php echo __('Sorry,your order have unpaid yet.',WSHOP);?>
    	    </div>
    	    <?php 
    	}?>
		
		<div class="block20"></div>
		<ul class="xh-orderinfo clearfix">

			<li><?php echo __('Order No:',WSHOP)?><strong><?php echo $order->id?></strong></li>

			<li><?php echo __('Order Date:',WSHOP)?><strong><?php echo date('Y-m-d H:i',$order->order_date)?></strong></li>

            <?php 
            $payment = $order->get_payment_gateway();
            if($payment){
                ?>
                <li><?php echo __('Payment method:',WSHOP)?><strong><?php echo $payment->title?></strong></li>
                <?php 
            }

            ?>
			<li><?php echo __('Total Amount:',WSHOP)?><strong><?php echo $order->get_total_amount(true)?></strong></li>
		</ul>
		
		<?php $order->order_items_view_order_received();?>
	</div>
	
</div>
<div style="text-align: center"> 
    <?php 
        $location = isset($order->metas['location'])?$order->metas['location']:null;
        if(!$location){$location = home_url('/');}
    ?>
    <a href="<?php echo $location;?>" class="xh-btn xh-btn-primary"><?php echo __('Go back',WSHOP)?></a> 
    <a href="<?php echo home_url('/');?>" class="xh-btn xh-btn-default "><?php echo __('Back to homepage',WSHOP)?></a>
</div>