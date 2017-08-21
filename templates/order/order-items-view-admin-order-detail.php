<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::get('atts','templates');
$order = $data['order'];
$order_items = $order->get_order_items();
?>
<div id="wshop-order-items" class="postbox ">
    <h2 class="hndle ui-sortable-handle"><span><?php echo __('Items',WSHOP)?></span></h2>
    <div class="inside">
    <?php if($order_items&&count($order_items)>0){?>
    <div class="wshop_order_items_wrapper">
    	<table  class="wshop_order_items">
    		<thead>
    			<tr>
    				<th class="item sortable" colspan="2" data-sort="string-ins"><?php echo __('Item',WSHOP)?></th>
    				<th class="item_cost sortable" data-sort="float"><?php echo __('Price',WSHOP)?></th>
    				<th class="quantity sortable" data-sort="int"><?php echo __('Qty',WSHOP)?></th>
    				<th class="line_cost sortable" data-sort="float"><?php echo __('Total',WSHOP)?></th>
    			</tr>
    		</thead>
    		<tbody id="order_line_items">
    			<?php 
			         foreach ($order_items as $order_item){
			        ?>
 			    	<tr class="item">
                        	<!-- <td class="thumb"><img alt="" src="<?php echo $order_item->get_img()?>" style="width:42px;height:42px;"> </td>-->
                             <td class="name" colspan="2">
                          <a href="<?php echo $order_item->get_link()?>" class="wshop-order-item-name"><?php echo esc_html($order_item->get_title())?></a>
                             <div class="view"></div>
             			</td>
             
                     	<td class="item_cost" width="2%" data-sort-value="0.01">
                     		<div class="view">
                     			<span class="wshop-Price-amount amount">
                     				<?php echo $order_item->get_price(true); ?>
                     			</span>		
                     		</div>
                     	</td>
                     	<td class="quantity" width="2%">
                     		<div class="view">
                     			<small class="times">×</small><?php echo $order_item->qty?>
                     		</div>
                     	</td>
                     	
                     	<td class="line_cost" width="2%">
                     		<div class="view">
                     			<span class="wshop-Price-amount amount">
                     			<?php echo $order_item->get_subtotal(true); ?>
                     			</span>		
                     		</div>
                     	</td>
                     </tr>
 			    <?php 
    		}?>
    			
    		</tbody>
	 	</table>
    </div>
    <?php }?>
 	<div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
		<table class="wc-order-totals">
		<tbody>
		<?php 
			if($order->extra_amount){
			    $symbol =WShop_Currency::get_currency_symbol($order->currency);
			     
			    foreach ($order->extra_amount as $key=>$att){
			        ?>
			        <tr>
            			<td class="label"><?php echo $att['title']?>：</td>
            			<td width="1%"></td>
            			<td class="total">
            				<span class="wshop-Price-amount amount"><?php echo "<span class=\"wshop-price-symbol\">$symbol</span>".WShop_Helper_String::get_format_price($att['amount']);?></span>			
            			</td>
            		</tr>
			       <?php
                }
            }
	      ?>
		<tr>
			<td class="label"><?php echo __('Total:',WSHOP)?></td>
			<td width="1%"></td>
			<td class="total">
				<span class="wshop-Price-amount amount"><?php echo $order->get_total_amount(true);?></span>			
        			</td>
        		</tr>
        	</tbody>
        	</table>
        	<div class="clear"></div>
        </div>
    </div>
</div>
<?php 