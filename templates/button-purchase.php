<?php 
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

$data = WShop_Temp_Helper::get('atts','templates');

$atts = $data['atts'];
$content = $data['content'];

$product = new WShop_Product($atts['post_id']);
if(!$product->is_load()){
    return;
}

$request_url =null;
if(!is_user_logged_in()&&!WShop::instance()->WP->is_enable_guest_purchase($atts)){
   
    $request_url=wp_login_url($atts['location']);
    ?><a href="<?php echo $request_url;?>" class="<?php echo isset($atts['class'])?esc_attr($atts['class']):""?>" style="<?php echo isset($atts['style'])?esc_attr($atts['style']):""?>"><?php echo do_shortcode($content);?></a><?php
    return;
}else{ 
    if(apply_filters('wshop_enable_page_order_pay',false, $atts)){
        $request = WShop_Async::instance()->shortcode_atts(WShop::instance()->payment->pay_atts(), $atts);
        $request['action']="wshop_checkout";
        $request['post_id'] = $atts['post_id'];
        $request['tab']="add_to_cart";
        $request_url =WShop::instance()->ajax_url($request,true,true);
   
        ?><a href="<?php echo $request_url;?>" class="<?php echo isset($atts['class'])?esc_attr($atts['class']):""?>" style="<?php echo isset($atts['style'])?esc_attr($atts['style']):""?>"><?php echo do_shortcode($content);?></a><?php
        return;
    }
}
 
$context = $atts['context'];
$payment_gateways =WShop::instance()->payment->get_payment_gateways();
if(count($payment_gateways)<=0){
    return;
}

if(count($payment_gateways)==1){
    $payment_gateway =$payment_gateways[0];
    /*--=-=-=-=--=-=-=-=--=-=-=-=--=-=-=-=--=-=-=按钮 START-=--=-=-=-=--=-=-=-=--=-=-=-=--=-=*/
    ?><a id="btn-pay-button-<?php echo $context?>" href="javascript:void(0);" class="<?php echo isset($atts['class'])?esc_attr($atts['class']):""?>" style="<?php echo isset($atts['style'])?esc_attr($atts['style']):""?>"><?php echo do_shortcode($content);?></a>
    <script type="text/javascript">
		(function($){
			$('#btn-pay-button-<?php echo $context?>').click(function(){
				<?php
				$request = WShop_Async::instance()->shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array(
				    'post_id'=>$atts['post_id']
				)), $atts);
				
				$request['action']="wshop_checkout";
				$request['tab']="fast_shopping";
				?>
				var data = <?php echo json_encode(WShop::instance()->generate_request_params($request,$request['action']))?>;
				data.payment_method = '<?php echo $payment_gateway->id?>';
				if($('#btn-pay-button-<?php echo $context?>').attr('data-loading')){
					return;
				}
				var pre_text = $('#btn-pay-button-<?php echo $context?>').text();
				$.ajax({
		            url: '<?php echo WShop::instance()->ajax_url()?>',
		            type: 'post',
		            timeout: 60 * 1000,
		            async: true,
		            cache: false,
		            data: data,
		            beforeSend:function(){
		            	$('#btn-pay-button-<?php echo $context?>').attr('data-loading','true').text('<?php echo __('Processing...',WSHOP)?>');
			        },
		            dataType: 'json',
		            success: function(m) {
		            	if(m.errcode!=0){
			            	if(m.errcode==501){
			            		location.href="<?php echo wp_login_url($atts['location'])?>";
			            		return;
				            }
			            	alert(m.errmsg);
			            	$('#btn-pay-button-<?php echo $context?>').removeAttr('data-loading').text(pre_text);
			            	return;
						}
						
		            	location.href=m.data;
		            },error:function(e){
			            console.warn(e.responseText);
		            	alert('<?php echo WShop_Error::err_code(500)->errmsg?>');
		            	$('#btn-pay-button-<?php echo $context?>').removeAttr('data-loading').text(pre_text);
			         }
		         });
			});
		})(jQuery);
	</script>
    <?php
    /*-=-=-=-=--=-=-=-=--=-=-=-=--=-=-=-=--=-=-=-按钮 END=--=-=-=-=--=-=-=-=--=-=-=-=--=*/
    
    return;
}
?>
<a href="javascript:void(0);" id="btn-pay-button-<?php echo $context?>" class="<?php echo isset($atts['class'])?esc_attr($atts['class']):""?>" style="<?php echo isset($atts['style'])?esc_attr($atts['style']):""?>"><?php echo do_shortcode($content);?></a>
<div class="wshop-pay-button" id="modal-wshop-button-<?php echo $context;?>" >
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

<script type="text/javascript">
	(function($){
		if(!$){
			throw 'jquery,js not found!';
		}

		var on_button_<?php echo $context?>_ui_resize=function(){
			var $ul =$('#modal-wshop-button-<?php echo $context;?> .xh-button-box');
			var $window = jQuery(window);
			if($window.width()>450){
				$ul.css({
					top:((document.documentElement.clientHeight - $ul.height()) / 2) + "px",
					left:((document.documentElement.clientWidth - $ul.width()) / 2) + "px"
				});
			}else{
				$ul.css({
					top:'',
					left:''
				});
			}
			
		};
		
		$('#btn-pay-button-<?php echo $context?>').click(function(){
			$('#modal-wshop-button-<?php echo $context;?> .xh-button-box .loading').hide();
			$('#modal-wshop-button-<?php echo $context;?>').css({'display':'block'});
			//让弹出框居中
			on_button_<?php echo $context?>_ui_resize();
		});	

		$(window).resize(function(){
			on_button_<?php echo $context?>_ui_resize();
		});
		
		$('#modal-wshop-button-<?php echo $context;?> .cover,#modal-wshop-button-<?php echo $context;?> .xh-button-box .close').click(function(){
			$('#modal-wshop-button-<?php echo $context;?>').hide();
		});

		$('#modal-wshop-button-<?php echo $context?> .xh-button-box .xh-item').click(function(){
			<?php
			$request = WShop_Async::instance()->shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array(
			    'post_id'=>$atts['post_id']
			)), $atts);
			
			$request['action']="wshop_checkout";
			$request['tab']="fast_shopping";
			?>
			var data = <?php echo json_encode(WShop::instance()->generate_request_params($request,$request['action']))?>;
			data.payment_method = $(this).attr('data-id');
			if($('#btn-pay-button-<?php echo $context?>').attr('data-loading')){
				return;
			}
			
			$.ajax({
	            url: '<?php echo WShop::instance()->ajax_url()?>',
	            type: 'post',
	            timeout: 60 * 1000,
	            async: true,
	            cache: false,
	            data: data,
	            beforeSend:function(){
	            	$('#btn-pay-button-<?php echo $context?>').attr('data-loading','true');
	            	$('#modal-wshop-button-<?php echo $context;?> .xh-button-box .loading').show();
		        },
	            dataType: 'json',
	            success: function(m) {
	            	if(m.errcode!=0){
		            	if(m.errcode==501){
		            		location.href="<?php echo wp_login_url($atts['location'])?>";
		            		return;
			            }
		            	alert(m.errmsg);
		            	$('#btn-pay-button-<?php echo $context?>').removeAttr('data-loading');
		            	$('#modal-wshop-button-<?php echo $context;?> .xh-button-box .loading').hide();
		            	return;
					}
					
	            	location.href=m.data;
	            },error:function(e){
		            console.warn(e.responseText);
	            	alert('<?php echo WShop_Error::err_code(500)->errmsg?>');
	            	$('#btn-pay-button-<?php echo $context?>').removeAttr('data-loading');
	            	$('#modal-wshop-button-<?php echo $context;?> .xh-button-box .loading').hide();
		         }
	         });
		});
	})(jQuery);
</script>