<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::get('atts','templates');
$order = $data['order'];

$order_items = $order->get_order_items();
?>
	<div class="block20"></div>
	<div class="xh-title"><?php echo __('Order details',WSHOP)?></div>
	<div class="block20"></div>
	<table class="xh-table">
<?php  if($order_items&&count($order_items)>0){?>
			<thead>
				<tr>
					<th><?php echo __('Product',WSHOP)?></th>
					<th style="width:20%;"><?php echo __('Subtotal',WSHOP)?></th>
				</tr>
			</thead>
			<?php foreach ($order_items as $order_item){
			    ?>
			    <tr>
    				<td><!-- <img src="<?php echo $order_item->get_img();?>" style="width:25px;height:25px;"/> --><span style="margin-left:10px;"><?php echo $order_item->get_title()?></span>x <?php echo $order_item->qty?></td>
    				<td><?php echo $order_item->get_subtotal(true)?></td>
    			</tr>
			    <?php 
			}
         }
         ?>
         
         <tr>
         <td style="text-align: left;">
         	<?php do_action('wshop_order_items_view_checkout_order_recieved_sections',$order);?>
         </td>
         <td style="text-align: left;">
         <?php 
    		if($order->extra_amount){
    		    $symbol =WShop_Currency::get_currency_symbol($order->currency);
    		     
    		    foreach ($order->extra_amount as $key=>$att){
    		      
    		      echo $att['title']; ?>:<strong> <?php echo "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($att['amount']);?></strong><br/><?php 
    			    
                }
            }
            
           echo __('Total',WSHOP);
           ?>:<strong> <?php echo $order->get_total_amount(true);?></strong>
      </td>
      
	</table>
	
    <?php 
?>