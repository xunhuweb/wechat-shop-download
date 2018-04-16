<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::clear('atts','templates');
$order = $data['order'];

$order_items = $order->get_order_items();
if(!$order_items){
    return;
}
?>
    <table>
    	<?php foreach ($order_items as $order_item){
    	    //<?php echo $order_item->get_link();
    	    ?>
    	    <tr>
        	   <td><img src="<?php echo $order_item->get_img();?>" style="width:25px;height:25px;"/></td>
        	    <td><?php echo $order_item->get_title();?></td>
        	    <td>x<?php echo $order_item->qty?></td>
        	</tr>
    	    <?php 
    	    
    	}?>
    	 
    </table>
    <?php 