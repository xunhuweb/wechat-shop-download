<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::clear('atts','templates');
$order = $data['order'];
?>
<div id="wshop-order-data" class="postbox ">
     <h2 class="hndle ui-sortable-handle"><span><?php echo __('Order data',WSHOP)?></span></h2>
    	<div class="inside">
    		<div class="panel-wrap wshop">
    			
    			<div id="order_data" class="panel">
    				<h2 class="order_data_title"><?php echo sprintf(__('Order #%s details'),$order->id)?></h2>
    				<?php 
    				    if($order->is_paid()){
    				        $payment_gateway = $order->get_payment_gateway();
    				        ?>
    				        <p class="order_number"><?php echo sprintf(__('via %s pay at %s.',WSHOP),$payment_gateway?$payment_gateway->title:"[null]", date('Y-m-d H:i',$order->order_date));?> <?php echo __('Customer IP:',WSHOP)?> <span class="wshop-Order-customerIP"><?php echo $order->ip?></span></p>
    				        <?php 
    				    }
    				?>
    				
    				<div class="order_data_column_container">
    					<div class="order_data_column">
    						<h3><?php echo __('General details',WSHOP)?></h3>
    						<?php if($order->is_paid()){
    						    ?>
    						    <p class="form-field form-field-wide">
        							<label for="pay_id"><?php echo __('Pay ID:',WSHOP)." ". $order->sn?></label>
        						</p>
        						<p class="form-field form-field-wide">
        							<label for="transaction_id"><?php echo __('Transaction ID:',WSHOP)." ". $order->transaction_id?></label>
        						</p>
    						    <?php 
    						}?>
    						<p class="form-field form-field-wide">
    							<label for="order_date"><?php echo __('Order Date:',WSHOP)." ". date('Y-m-d H:i',$order->order_date)?></label>
    						</p>
    
    						<p class="form-field form-field-wide wshop-order-status">
    							<label for="order_status"><?php echo __('Order Status:',WSHOP)." ".$order->get_order_status_html()?> </label>
    						</p>
    						<?php 
    						if($order->customer_id){
    						    $user = get_user_by('id', $order->customer_id);
    						    if($user){
    						        ?>
    						        <p class="form-field form-field-wide wshop-order-status">
            							<label for="order_status"><?php echo __('Customer:',WSHOP) ." {$user->user_login}({$user->user_email})"?>  
            							
            							<a href="<?php echo WShop_Admin::instance()->get_current_admin_url(array('_cid'=>$user->ID))?>"><?php echo __('View other orders →',WSHOP)?></a></label>
            						</p>
    						        <?php 
    						    }
    						}
    						?>
    					</div>
    					<?php do_action('wshop_order_view_admin_order_detail_order_data_column',$order);?>
    				</div>
    				<div class="clear"></div>
    			</div>
    		</div>
    		
    	</div>
    </div>
<?php 