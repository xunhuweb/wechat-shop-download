<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::clear('atts','templates');
$order = $data['order'];

if($order->is_paid()){
  ?><div class="xh-title"><?php echo __('Order received',WSHOP)?></div>
    <div class="block20"></div>
    <div  class="clearfix" style="color:green;"><?php echo __('Thanks,we have received your order.',WSHOP)?></div><?php 
}else if($order->is_canceled()){
  ?><div class="xh-title"><?php echo __('Order canceled',WSHOP)?></div>
    <div class="block20"></div>
    <div  class="clearfix" style="color:red;"><?php echo __('Sorry,your order has been canceled.(If you has been paid ,contact administrator to refund )',WSHOP)?></div><?php 
}else{
  ?><div class="xh-title clearfix"><?php echo __('Waitting for payment',WSHOP); 
       if($order->can_pay()){
           ?> <a class="xh-pull-right xh-btn xh-btn-sm xh-btn-primary" href="<?php echo $order->get_pay_url()?>"><?php echo __('Pay',WSHOP)?></a>
           <?php
       }
  ?></div>
    <div class="block20"></div>
    <div class="clearfix" style="color:red;">
    	<?php echo __('Sorry,your order have unpaid yet.',WSHOP);?>
    </div>
    <?php 
}