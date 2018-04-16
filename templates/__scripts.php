<?php 
$payment_gateways =WShop::instance()->payment->get_payment_gateways();
?>
<?php do_action('wshop_footer',$payment_gateways);?>
<div class="wshop-pay-button" id="wshop-modal-payment-gateways"  style="display:none;">
	<div class="cover"></div>
	<div class="xh-button-box">
		<div class="close"></div>
		<div class="loading"></div>
		<?php if($payment_gateways){
		    $qty = count($payment_gateways);
		    $index=0;
		    foreach ($payment_gateways as $payment_gateway){
		        ?><div class="xh-item" data-id="<?php echo esc_attr($payment_gateway->id);?>" style="<?php echo $index++==$qty-1?"border-bottom:0;":"";?>"><i style="background: url(<?php echo empty($payment_gateway->icon_small)?$payment_gateway->icon:$payment_gateway->icon_small;?>) center no-repeat;"></i><span><?php echo esc_html($payment_gateway->title);?></span></div><?php 
		    }
		}?>
	</div>
</div>

<div class="wshop-pay-button" id="wshop-modal-payment-gateways-1" style="display:none;">
	<div class="cover"></div>
    <div class="mod-ct">
        <div class="amount" id="wshop-modal-payment-gateways-1-amount"></div>
        <div class="qr-image" align="center">
        	<img style="width:220px;height:220px" src="" id="wshop-modal-payment-gateways-1-qrcode" />
        </div>
        <div class="tip">
            <div class="tip-text">
            <div style="display:none;" id="shop-modal-payment-gateways-payment-method-pre">   请使用 <?php 
              $is_any = false;
              if(WShop_Helper_Array::any($payment_gateways,function($m){return $m->group=='alipay';})){
                  $is_any=true;
                  ?> <i class="icon alipay"></i>支付宝<?php    
              }
              if(WShop_Helper_Array::any($payment_gateways,function($m){return $m->group=='wechat';})){
                  if($is_any){echo ' 或 ';}
                  $is_any=true;
                  ?> <i class="icon weixin"></i>微信<?php
              }?>
                     扫码支付</div>
                <div class="channel center" id="shop-modal-payment-gateways-payment-method">
                     
                </div>
            </div>
        </div>
        <a class="xh-close" href="javascript:void(0);"></a>
    </div>
</div>