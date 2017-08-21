<?php 
if (! defined('ABSPATH')) {
    exit();
}

$data = WShop_Temp_Helper::get('atts','templates');

$request = $data['request'];
$context = $request['context'];
$content = $data['content'];

$symbol =WShop_Currency::get_currency_symbol(WShop::instance()->payment->get_currency());
?>
<div class="block20"></div>
        
<div class="clearfix xh-checkoutbg" >
    <span class="xh-total-price xh-pull-left" style="display:none;" id="wshop-<?php echo $context?>-actual-amount"></span>
   <?php 
   if(!is_user_logged_in()&&!$request['enable_guest']){
      ?>
      <a href="<?php echo wp_login_url($request['location'])?>" class="xh-btn xh-btn-warring xh-btn-lg xh-pull-right"><?php echo $content;?></a>
      <?php 
   }else{
      ?>
      <a href="javascript:void(0);" id="btn-pay-now-<?php echo $context?>" class="xh-btn xh-btn-warring xh-btn-lg xh-pull-right"><?php echo $content;?></a>
      <?php  
   }
   ?>
    
</div>
<script type="text/javascript">
	(function($){
			window.wshop_view_<?php echo $context?>={
				total_amount:0,
				extra_amount:[],
				
				symbol:'<?php echo $symbol;?>',	

				//初始化钩子函数
				init:function(){
					
					$(document).bind('wshop_<?php echo $context?>_on_amount_change',function(e){
						var view =window.wshop_view_<?php echo $context?>;
						$(document).trigger('wshop_<?php echo $context?>_display_amount',view);
		    		});
		    		
					$(document).bind('wshop_<?php echo $context?>_display_amount',function(e,view){
						
						//处理total_amount
						view.total_amount=0;
						$(document).trigger('wshop_<?php echo $context?>_init_amount_before',view);
						var extra_amount_pre =view.extra_amount;
						
						//处理extra_amount
						view.extra_amount=[];
						$(document).trigger('wshop_<?php echo $context?>_init_amount',view);
						
						//计算折扣
						var extra_amount=0;
						for(var i=0;i<view.extra_amount.length;i++){
							var amount0 =view.extra_amount[i];
							extra_amount+=amount0.amount;
						}

						view.total_amount = view.total_amount+extra_amount;

						//整合 extra_amount_pre，extra_amount
						for(var i=0;i<extra_amount_pre.length;i++){
							view.extra_amount.push(extra_amount_pre[i]);
						}
						
						//在这个钩子内，对总金额进行处理
						$(document).trigger('wshop_<?php echo $context?>_init_amount_after',view);
						
						var total =view.total_amount;

						//TODO 把折扣信息（extra_amount）显示出来
						//...
						if(total<=0){
							$('#wshop-<?php echo $context?>-actual-amount').html('').hide();
						}else{
							$('#wshop-<?php echo $context?>-actual-amount').html('<?php echo __('Total:')?>'+view.symbol+total.toFixed(2)).show();
						}
		    		});
		    		
		    		$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text('<?php echo $content;?>');
					$('#btn-pay-now-<?php echo $context?>').click(function(){
						var data = <?php echo json_encode(WShop::instance()->generate_request_params($request,$request['action']));?>	;
						
						$(document).trigger('wshop_form_<?php echo $context?>_submit',data);
						
						$('#btn-pay-now-<?php echo $context?>').attr('disabled','disabled').text('<?php echo __('Processing...',WSHOP)?>');

						$.ajax({
				            url: '<?php echo WShop::instance()->ajax_url()?>',
				            type: 'post',
				            timeout: 60 * 1000,
				            async: true,
				            cache: false,
				            data: data,
				            dataType: 'json',
				            success: function(m) {
				            	if(m.errcode!=0){
					            	if(m.errcode==501){
					            		location.href="<?php echo wp_login_url($request['location'])?>";
					            		return;
						            }
					            	alert(m.errmsg);
					            	$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text('<?php echo $content?>');
					            	return;
								}
								
				            	location.href=m.data;
				            },error:function(e){
					            console.warn(e.responseText);
				            	alert('<?php echo WShop_Error::err_code(500)->errmsg?>');
				            	$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text('<?php echo $content?>');
					         }
				         });
					});
				}
			}

			window.wshop_view_<?php echo $context?>.init();
			$(document).trigger('wshop_<?php echo $context?>_on_amount_change');
	})(jQuery);
</script>
<?php 

do_action('wshop_checkout_order_pay_btn',$request);
?>