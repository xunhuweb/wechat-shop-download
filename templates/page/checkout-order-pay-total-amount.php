<?php 
if (! defined('ABSPATH')) {
    exit();
}

$data = WShop_Temp_Helper::clear('atts','templates');
$context = $data['context'];
$symbol =WShop_Currency::get_currency_symbol(WShop::instance()->payment->get_currency());
?>
<script type="text/javascript">
	jQuery(function($){
		window.wshop_view_<?php echo $context?>={
			total_amount:0,
			extra_amount:[],
			
			symbol:'<?php echo $symbol;?>',	

			init:function(){
				
				$(document).bind('wshop_<?php echo $context?>_on_amount_change',function(e){
					var view =window.wshop_view_<?php echo $context?>;
					$(document).trigger('wshop_<?php echo $context?>_display_amount',view);
	    		});
	    		
				$(document).bind('wshop_<?php echo $context?>_display_amount',function(e,view){
					
					view.total_amount=0;
					$(document).trigger('wshop_<?php echo $context?>_init_amount_before',view);
					var extra_amount_pre =view.extra_amount;
					
					view.extra_amount=[];
					$(document).trigger('wshop_<?php echo $context?>_init_amount',view);
					
					var extra_amount=0;
					for(var i=0;i<view.extra_amount.length;i++){
						var amount0 =view.extra_amount[i];
						extra_amount+=amount0.amount;
					}

					view.total_amount = view.total_amount+extra_amount;

					for(var i=0;i<extra_amount_pre.length;i++){
						view.extra_amount.push(extra_amount_pre[i]);
					}
					
					$(document).trigger('wshop_<?php echo $context?>_init_amount_after',view);
					
					$(document).trigger('wshop_<?php echo $context?>_show_amount',view);
	    		});
			}
		}
		window.wshop_view_<?php echo $context?>.init();
		$(document).trigger('wshop_<?php echo $context?>_on_amount_change');
	});
</script>