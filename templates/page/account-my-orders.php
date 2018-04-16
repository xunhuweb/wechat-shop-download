<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$data = WShop_Temp_Helper::clear('atts','templates');
$location = isset($data['location'])?$data['location']:null;
$pageSize = isset($data['pageSize'])?intval($data['pageSize']):20;
if(!is_user_logged_in()){
   ?>
   <script type="text/javascript">
		location.href='<?php echo wp_login_url(WShop_Helper_Uri::get_location_uri())?>';
	</script>
   <?php
    return;
}

$pageIndex = isset($_REQUEST['pageIndex'])?absint($_REQUEST['pageIndex']):0;
if($pageIndex<1){
    $pageIndex=1;
}

$user_id = get_current_user_id();

global $wpdb;
$query =$wpdb->get_row(
   "select count(o.id) as qty
    from {$wpdb->prefix}wshop_order o
    where o.removed=0
          and o.status!='".WShop_Order::Unconfirmed."'
          and o.customer_id ={$user_id};");
$total_qty = intval($query->qty);
if($total_qty>0&&$pageIndex>$total_qty){
    $pageIndex = $total_qty;
}

$pageCount = absint(ceil($total_qty/($pageSize*1.0)));
$start = ($pageIndex-1)*$pageSize;

$orders = $wpdb->get_results(
    "select o.*
    from {$wpdb->prefix}wshop_order o
    where o.removed=0
          and o.status!='".WShop_Order::Unconfirmed."'
          and o.customer_id ={$user_id}
    order by o.id desc
    limit $start,$pageSize;");
?>
 <div class="xh-layout">
    <div class="title"><?php echo __('My Orders',WSHOP)?></div>
     <table class="xh-table">
     <thead>				
         <tr>
             <th style="width: 15%"><?php echo __('ID',WSHOP)?></th>
             <th style="width: 25%"><?php echo __('Date',WSHOP)?></th>
             <th style="width: 20%"><?php echo __('Status',WSHOP)?></th>
             <th style="width: 20%"><?php echo __('Total',WSHOP)?></th>
             <th><?php echo __('Toolbar',WSHOP)?></th>
         </tr>
     </thead>
     <?php if(!$orders||count($orders)==0){
         ?>
         <tr>
             <td colspan="5"><?php echo __( "You don't have any orders!", WSHOP ) ;?></td>
         </tr>
         <?php 
     }else{
         foreach ($orders as $wp_order){
             $order = new WShop_Order($wp_order);
             
             ?><tr>
                 <td>#<?php echo $order->id?></td>
                 <td><?php echo date('Y-m-d H:i',$order->order_date)?></td>
                 <td><?php echo $order->get_order_status_html()?></td>
                 <td> <?php echo $order->get_total_amount(true)?></td>
                 <td>  
                     <a href="<?php echo $order->get_review_url()?>" class="xh-btn xh-btn-xs xh-btn-default"><?php echo __('View',WSHOP)?></a> 
                    <?php if($order->can_pay()){
                        ?><a href="<?php echo $order->get_pay_url()?>" class="xh-btn xh-btn-xs xh-btn-primary"><?php echo __('Pay',WSHOP)?></a><?php 
                    }?>
                     
                 </td>
             </tr><?php 
         }
     }?>
     </table>
     <div class="clearfix xh-pull-right mB20" >
       <?php 
       require_once WSHOP_DIR.'/includes/paging/class-xh-paging-model.php';
       if(empty($location)){
           $location = WShop_Helper_Uri::get_location_uri();
       }
       $pagging = new WShop_Paging_Model($pageIndex, $pageSize, $total_qty,function($pageIndex,$location){
           return WShop_Helper_Uri::get_new_uri($location,array('pageIndex'=>$pageIndex));
       },$location);
       echo $pagging->bootstrap();
       ?>
      </div>
 </div>