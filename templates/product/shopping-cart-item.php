<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = WShop_Temp_Helper::clear('atts','templates');
$qty = intval($data['qty']);
$product = $data['product'];
if(!$product instanceof Abstract_WShop_Product){
    return;
}
$context =$data['context'];
$inventory = $product->get('inventory');
$now = current_time( 'timestamp' );

?>

<dl class="xh-prolist clearfix ">
    <dt><a href="<?php echo $product->get_link()?>"><img src="<?php echo $product->get_img()?>" style="width:50px;height:50px;"/></a></dt>
    <dd>
        <div class="ptitle"><?php echo esc_html($product->get_title())?></div>
        <p class="price"><?php echo $product->get_single_price(true)?></p>
    </dd>
 
 	<?php if(is_null($inventory)){
 	    ?><div class="j-item2"> <span class="item-num" style="display: block;">×<?php echo $qty;?></span></div>
 	     <script type="text/javascript">
            	(function($){
            		$(document).bind('wshop_<?php echo $context;?>_init_amount_before',function(e,m){
            			var price =<?php echo $product->get_single_price(false)?>;
            			m.total_amount+=price;
            		});
            	})(jQuery);
            </script>
            <?php 
 	}else if($inventory<=0){
 	    ?> <div class="j-item2">  <span style="color: red;"><?php echo __('Sold Out',WSHOP)?></span></div><?php 
 	}else{
 	    ?> <div class="j-item2">
 	    	<div class="xh-input-group" style="width:90px;">
                <span class="xh-input-group-btn">
                	<button class="xh-btn xh-btn-default xh-numbtn" style="width:30px;" type="button" id="btn-<?php echo $context?>-<?php echo $product->post_ID?>-cut-qty">-</button>
                </span>
                <input id="txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty" name="code" type="text" class="form-control" value="<?php echo $qty;?>" style="width:30px;border-left:0;border-right:0;border-radius: 0;border-radius: 0;padding:6px 0;text-align:center;" />
                <span class="xh-input-group-btn">
                    <button class="xh-btn xh-btn-default" style="width:30px;" type="button"  id="btn-<?php echo $context?>-<?php echo $product->post_ID?>-add-qty">+</button>
               </span>
            </div>
           </div>
           <?php 
            $request = array();
            $request['action']='wshop_checkout_v2';
            $request['tab']='shopping_cart_change_qty';
            $request = WShop::instance()->generate_request_params($request,$request['action']);
            ?>
            <script type="text/javascript">
            	(function($){
            		window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty =<?php echo is_null($inventory)?99:intval($inventory);?>;
            		window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty =<?php echo 0;?>;
            		window.on_product_<?php echo $context?>_<?php echo $product->post_ID?>_qty_change=function(qty){
                		var data=<?php echo json_encode($request)?>;
                		data.post_id=<?php echo $product->post->ID;?>;
                		data.qty = qty;
            			$.ajax({
							url:'<?php echo WShop::instance()->ajax_url()?>',
							type:'post',
							timeout:60*1000,
							async:true,
							cache:false,
							data:data,
							dataType:'json',
							success:function(e){
								if(e.errcode!=0){
									alert(e.errmsg);
									location.reload();
									return;
								}
							},
							error:function(e){
								console.error(e.responseText);
								alert('<?php echo esc_attr( 'System error while change qty!', WSHOP); ?>');
								location.reload();
							}
						});
                	};
                	
            		$('#btn-<?php echo $context?>-<?php echo $product->post_ID?>-cut-qty').click(function(){
            			if($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val()){
            			var qty = parseInt($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val());
            			if(isNaN(qty)||qty<=window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
            				qty=window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty+1;
            			}
            
            			qty--;
            			$('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val(qty);
            			window.on_product_<?php echo $context?>_<?php echo $product->post_ID?>_qty_change(qty);
            			$(document).trigger('wshop_<?php echo $context?>_on_amount_change');}
                	});
                
                	$('#btn-<?php echo $context?>-<?php echo $product->post_ID?>-add-qty').click(function(){
                		if($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val()){
                    		var qty = parseInt($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val());
                		if(isNaN(qty)||qty>=window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
                			qty=window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty-1;
                		}
                
                		qty++;
                		$('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val(qty);
                		window.on_product_<?php echo $context?>_<?php echo $product->post_ID?>_qty_change(qty);
                		$(document).trigger('wshop_<?php echo $context?>_on_amount_change');}
                	});
                
                	$('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').keyup(function(){
                		if($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val()){
                        	var qty = parseInt($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val());
                    		if(isNaN(qty)||qty<=window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
                    			qty=window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty;
                    		}	
                
                    		if(qty>=window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
                    			qty=window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty;
                    		}
                    		
                    		$('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val(qty);
                    		window.on_product_<?php echo $context?>_qty_change(qty);
                    		$(document).trigger('wshop_<?php echo $context?>_on_amount_change');
                		}
                	});
            		
            		$(document).bind('wshop_<?php echo $context;?>_init_amount_before',function(e,m){
            			var price =<?php echo $product->get_single_price(false)?>;
            
            			var qty = parseInt($('#txt-<?php echo $context?>-<?php echo $product->post_ID?>-qty').val());
            			if(isNaN(qty)||qty<window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
            				qty=window.min_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty;
                		}
            			if(qty>window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty){
            				qty=window.max_<?php echo $context?>_<?php echo $product->post_ID?>_product_qty;
                		}
                		
            			m.total_amount+=price*qty;
            		});
            
            		
            	})(jQuery);
            </script><?php 
 	}?>
</dl>

