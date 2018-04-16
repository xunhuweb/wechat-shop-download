<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::clear('atts','templates');
$order = $data['order'];

if($order->is_paid()){
    ?>
     <div class="xh-title-h3 clearfix" style="text-align: center;">  <span ><?php echo __('Order received',WSHOP)?></span> </div>
    <?php 
}else if($order->is_canceled()){
    ?>
    <div class="xh-title-h3 clearfix" style="text-align: center;">  <span ><?php echo __('Order canceled!',WSHOP)?></span> </div>
    <?php
}else{
    ?>
    <div class="xh-title-h3 clearfix" style="text-align: center;">  <span ><?php echo __('Waitting for payment!',WSHOP)?></span> </div>
    <?php
}