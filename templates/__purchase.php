<?php 
/**
 * 提供三种模式的结算样式
 * 
 * 1.弹窗选择多支付方式
 * 2.弹窗扫码支付
 * 3.购物车模式的结算页面
 * 
 * @version 1.0.3
 */
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

$data = WShop_Temp_Helper::clear('atts','templates');

$context = isset($data['context'])?$data['context']:null;
if(empty($context)){
    $context = WShop_Helper::generate_unique_id();
}
$style = isset($data['style'])?$data['style']:null;
$class = isset($data['class'])?$data['class']:'xh-btn xh-btn-danger xh-btn-lg';
$location = isset($data['location'])&&!empty($data['location'])?esc_url_raw($data['location']):WShop_Helper_Uri::get_location_uri();
$content = isset($data['content'])&&!empty($data['content'])?$data['content']:__('Pay now',WSHOP);

//定义 支付模式
$modal = isset($data['modal'])&&!empty($data['modal'])?$data['modal']: WShop_Settings_Checkout_Options::instance()->get_option('modal','shopping_list');
//定义支付接口
$section = isset($data['section'])&&!empty($data['section'])?$data['section']: $modal;

if(!is_user_logged_in()&&!WShop::instance()->WP->is_enable_guest_purchase()){
    $request_url=wp_login_url($location);
    ?><a href="<?php echo $request_url;?>" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
    return;
}

$ajax_request_url = WShop::instance()->ajax_url(array(
    'action'=>'wshop_checkout_v2',
    'tab'=>'create_order',
    'modal'=>$modal,
    'section'=>$section
),true,true);

?>
 <script type="text/javascript">
 	(function($){
    	$(document).bind('wshop_form_<?php echo $context?>_submit',function(e,settings){
    		 settings.ajax.url = '<?php echo esc_url_raw($ajax_request_url)?>';
    		 settings.location='<?php echo $location;?>';
    	});
	 })(jQuery);
 </script>
<?php 

if(!function_exists('generate_wshop_btn_esc')){
    function generate_wshop_btn_esc($modal,$context,$data){
        //移动端，不出现扫码
        if($modal=='shopping_one_step'){
            if(WShop_Helper_Uri::is_app_client()){
                $modal = 'shopping_list';
            }
        }
        
        $style = isset($data['style'])?$data['style']:null;
        $class = isset($data['class'])?$data['class']:'xh-btn xh-btn-danger xh-btn-lg';
        $content = isset($data['content'])&&!empty($data['content'])?$data['content']:__('Pay now',WSHOP);
        
        switch ($modal){
            case 'shopping_list':
                ?><a id="btn-pay-button-<?php echo $context?>" onclick="window.wshop_jsapi.shopping_list('<?php echo $context?>');" href="javascript:void(0);" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
                break;
            case 'shopping_one_step':
                ?><a id="btn-pay-button-<?php echo $context?>" onclick="window.wshop_jsapi.shopping_one_step('<?php echo $context?>');" href="javascript:void(0);" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
                break;
            case 'shopping_cart':
                ?> <a id="btn-pay-button-<?php echo $context?>" onclick="window.wshop_jsapi.shopping_cart('<?php echo $context?>');" href="javascript:void(0);" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
                break;
            case 'shopping':
               ?><a id="btn-pay-button-<?php echo $context?>" onclick="window.wshop_jsapi.shopping('<?php echo $context?>');" href="javascript:void(0);" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
               break;
           default:
               ob_start();
               ?><a id="btn-pay-button-<?php echo $context?>" onclick="window.wshop_jsapi.shopping('<?php echo $context?>');" href="javascript:void(0);" class="<?php echo $class?>" style="<?php echo $style?>"><?php echo do_shortcode($content);?></a><?php
              echo apply_filters('wshop_btn_esc', ob_get_clean(),$modal,$context,$data);
              
              //立即调用，立即销毁，避免被其他短码调用
              remove_all_filters('wshop_btn_esc');
              break;
        }
    }
}

generate_wshop_btn_esc(apply_filters('wshop_purchase_modal', $modal,$context,$data),$context,$data);
remove_all_filters('wshop_purchase_modal');   