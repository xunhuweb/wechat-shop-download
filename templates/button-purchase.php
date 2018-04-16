<?php 
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

$data = WShop_Temp_Helper::clear('atts','templates');

$atts = $data['atts'];
$content = $data['content'];

$product = new WShop_Product($atts['post_id']);
if(!$product->is_load()){
    ?><span style="color:red;">[商品未设置价格或未启用在线支付！]</span><?php
    return;
}
$context = WShop_Helper::generate_unique_id();
?>
 <script type="text/javascript">(function($){
	 $(document).bind('wshop_form_<?php echo $context?>_submit',function(e,settings){settings.post_id = <?php echo $product->post_ID?>;});})(jQuery);
 </script>
<?php 
echo WShop::instance()->WP->requires(WSHOP_DIR, '__purchase.php',array(
    'content'=>$content,
    'style'=>isset($atts['style'])?$atts['style']:null,
    'class'=>isset($atts['class'])?$atts['class']:null,
    'location'=>isset($atts['location'])?$atts['location']:null,
    'context'=>$context,
    'modal'=>isset($atts['modal'])?$atts['modal']:null,
    'section'=>isset($atts['section'])?$atts['section']:null,
));