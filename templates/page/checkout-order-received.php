<?php
if (! defined('ABSPATH')) {
    exit();
}
$data = WShop_Temp_Helper::clear('atts','templates');
$order = isset($data['order'])?$data['order']:null;
if(!$order||!$order instanceof WShop_Order){
    WShop::instance()->WP->wp_die(WShop_Error::err_code(404),false,false);
    return;
}

?>
<style>
.xh-form{border:3px solid #dadada;
        border-radius: 6px;
    -webkit-box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);
    -moz-box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);
    box-shadow: 0px 3px 3px 0px rgba(0,0,0,0.04);}
</style>
<div class="xh-layout">
	<?php $order->order_view_title_order_received(); ?>

	<div class="xh-form">
	
	<?php $order->order_view_desc_order_received(); ?>
		
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