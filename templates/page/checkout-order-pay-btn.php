<?php 
if (! defined('ABSPATH')) {
    exit();
}

$data = WShop_Temp_Helper::get('atts','templates');

$request = $data['request'];
$content = $data['content'];
$context = $data['context'];
$class = isset($data['class'])?$data['class']:'xh-btn xh-btn-warning xh-btn-lg xh-pull-right';
if(!is_user_logged_in()&&!WShop::instance()->WP->is_enable_guest_purchase()){
  ?>
  <a href="<?php echo wp_login_url(WShop_Helper_Uri::get_location_uri())?>" class="<?php echo $class;?>"><?php echo $content;?></a>
  <?php 
}else{
  ?>
  <a href="javascript:void(0);" id="btn-pay-now-<?php echo $context?>" class="<?php echo $class;?>"><?php echo $content;?></a>
  <?php  
}
?>
<script type="text/javascript">
	(function($){
		$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text('<?php echo $content;?>');
		$('#btn-pay-now-<?php echo $context?>').click(function(){
			var data = <?php echo json_encode(WShop::instance()->generate_request_params($request,$request['action']))?>;
			
			$(document).trigger('wshop_form_<?php echo $context?>_submit',data);

			var btn_submit_txt = $('#btn-pay-now-<?php echo $context?>').text();
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
		            		location.href="<?php echo wp_login_url(WShop_Helper_Uri::get_location_uri())?>";
		            		return;
			            }
		            	alert(m.errmsg);
		            	$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text(btn_submit_txt);
		            	return;
					}
					setTimeout(function(){
						$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text(btn_submit_txt);
					},2000);
	            	location.href=m.data;
	            },error:function(e){
		            console.warn(e.responseText);
	            	alert('<?php echo WShop_Error::err_code(500)->errmsg?>');
	            	$('#btn-pay-now-<?php echo $context?>').removeAttr('disabled').text(btn_submit_txt);
		         }
	         });
		});
	})(jQuery);
</script>
<?php 

do_action('wshop_checkout_order_pay_btn',$request);
?>