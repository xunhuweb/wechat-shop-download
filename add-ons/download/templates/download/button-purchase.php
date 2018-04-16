<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
$data = WShop_Temp_Helper::clear('atts','templates');

$atts = $data['atts'];
$content = $data['content'];

$content = empty($content)?__('Download now',WSHOP):$content;
 
$post_id = isset($atts['post_id'])?$atts['post_id']:null;
if(!$post_id){return;}

$download = new WShop_Download($post_id);
if(!$download->is_load()){
    return;
}

$product = new WShop_Product($post_id );
if(!$product->is_load()){
    return;
}

$roles = isset($atts['roles'])?$atts['roles']:null;
if(WShop::instance()->payment->is_validate_get_pay_per_view($post_id,empty($roles)?null:explode(',', $roles))){
   ?><div style="border:1px dashed #f80000;padding:15px;text-align: center;"><?php 
    echo do_shortcode($download->downloads);
    ?></div><?php 
    return;
}

?>
<div style="border:1px dashed #f80000;padding:15px;text-align: center;">隐藏内容：
<span style="color: red">********,<?php echo WShop::instance()->WP->requires(WSHOP_DIR, 'button-purchase.php',array(
    'content'=>"支付".$product->get_single_price(true),
    'atts'=>$atts
));?></span>下载
<?php 
if(function_exists('wshop_dialog_membership_checkout')){
    ?>
    ，或者<?php wshop_dialog_membership_checkout(array(
        'class'=>'xh-btn xh-btn-warning xh-btn-sm',
    ),'升级VIP')?>可下载全站内容。
    <?php 
}
?>           
</div>