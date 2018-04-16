<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'class-wshop-wp-api.php';
/**
 * wordpress apis
 * 
 * @author rain
 * @since 1.0.0
 */
class WShop_Hooks{
    public static function init(){
        if(!defined('xh_http_headers_useragent')){
            define('xh_http_headers_useragent', 1);
            add_filter( 'http_headers_useragent',__CLASS__.'::http_build',99,1);
        }
        add_action( 'admin_print_footer_scripts', __CLASS__.'::post_edit_footer_secripts',10);
        add_filter('wshop_order_order_ordered',  __CLASS__.'::wshop_order_order_ordered',999,2);
        add_action( 'admin_print_footer_scripts',  __CLASS__."::wp_print_footer_scripts",999);
        add_action( 'wp_print_footer_scripts', __CLASS__."::wp_print_footer_scripts",999);
        
        add_filter('wshop_create_order_shopping_one_step_func', __CLASS__.'::create_order_shopping_one_step_func',10,3);
        add_filter('wshop_create_order_shopping_cart_func', __CLASS__.'::create_order_shopping_cart_func',10,3);
        add_filter('wshop_create_order_checkout', __CLASS__.'::create_order_checkout',10,3);
        add_filter('wshop_confirm_order_shopping_one_step', __CLASS__.'::confirm_order_shopping_one_step',10,3);
        
        
        WShop_Async::instance()->async('wshop_price',  __CLASS__."::wshop_price");
        WShop_Async::instance()->async('wshop_btn_add_to_cart', 'wshop_btn_add_to_cart');       
        WShop_Async::instance()->async('wshop_account_my_orders',  __CLASS__."::wshop_account_my_orders");
    
        //让所有产品图片有默认值
        add_action('save_post', __CLASS__.'::autoset_featured',10,3);       
        add_action('wp_print_footer_scripts',  __CLASS__.'::wechat_shop_scripts',99);
        
        //兼容老版本按钮支付
        WShop_Async::instance()->async('xhshop-btn-pay',  __CLASS__."::xhshop_btn_pay");
        WShop_Async::instance()->async('xhshop-is-paid',  __CLASS__."::xhshop_is_paid");
        WShop_Async::instance()->async('xhshop-is-not-paid',  __CLASS__."::xhshop_is_not_paid");
    }
  
    public static function create_order_checkout($funcs,$cart,$request){
        return array(
            function($cart,$request){
                $payment_method = isset($request['payment_method'])?$request['payment_method']:null;
                
                return $cart->__set_payment_method($payment_method);
                //save_changes();
            }
        );
    }
    
    /**
     * 
     * @param unknown $funcs
     * @param WShop_Shopping_Cart $cart
     * @param unknown $request
     */
    public static function create_order_shopping_cart_func($funcs,$cart,$request){
        return function($cart,$request){
            /**
             * 当前接口不创建订单
            $order =$cart->create_order($request['section']);
            if($order instanceof WShop_Error){
                return $order;
            }
             */
            return WShop_Error::success(WShop::instance()->payment->get_order_checkout_url());
        };
    }
    
    public static function create_order_shopping_one_step_func($func,$cart,$request){
        return function($cart,$request){
            $order =$cart->create_order($request['section']);
            if($order instanceof WShop_Error){
                return $order;
            }
            
            $action ='wshop_checkout_v2';
            $pay_url = WShop::instance()->ajax_url(array(
                'action'=>$action,
                'tab'=>'confirm_order_v',
                'modal'=>'shopping_one_step',
                'order_id'=>$order->id
            ),true,true);
             
            if(!class_exists('QRcode')){
                require_once WSHOP_DIR.'/includes/phpqrcode/phpqrcode.php';
            }
            
            $errorCorrectionLevel = 'L'; // 容错级别
            $matrixPointSize = 9; // 生成图片大小
             
            ob_start();
            
            QRcode::png($pay_url,false,$errorCorrectionLevel,$matrixPointSize);
            $imageString = "data:image/png;base64,".base64_encode(ob_get_clean());
            
            return WShop_Error::success(array(
                //这个参数不能去掉
                'url'=>WShop::instance()->ajax_url(array(
                    'action'=>'wshop_checkout_v2',
                    'tab'=>'confirm_order',
                    'order_id'=>$order->id,
                    'modal'=>isset($request['modal'])?$request['modal']:null,
                ),true,true),
                'qrcode_url'=>$imageString,
                'url_query'=> WShop::instance()->ajax_url(array(
                    'action'=>$action,
                    'tab'=>'is_paid',
                    'order_id'=>$order->id
                ),true,true),
                'price_html'=>$order->get_total_amount(true)
            ));
        };
    }
    
    public static function confirm_order_shopping_one_step($func,$order,$request){
        return array(function($order,$request){
            //适配移动端
            if(isset($request['payment_method'])){
                $order->set_change('payment_method',$request['payment_method']);
                return $order;
            }
            
            $payment_gateways=WShop::instance()->payment->get_payment_gateways();
            if(WShop_Helper_Uri::is_wechat_app()){
                $payment_gateway =WShop_Helper_Array::first_or_default($payment_gateways,function($m){return $m->group=='wechat';});
                if(!$payment_gateway){
                    return WShop_Error::error_custom('Sorry,Current order do not support wechat payment!',WSHOP);
                }
                $order->set_change('payment_method',$payment_gateway->id);
            }else{
                $payment_gateway =WShop_Helper_Array::first_or_default($payment_gateways,function($m){return $m->group=='alipay';});
                if(!$payment_gateway){
                    return  WShop_Error::error_custom('Sorry,Current order do not support alipay payment!',WSHOP);
                }
                $order->set_change('payment_method',$payment_gateway->id);
            }
          
            return $order;//->save_changes();
        });
    }
    
    public static function xhshop_is_paid($atts=array(),$content = null){
        if(!is_array($atts)){$atts=array();}
        $post_ID = isset( $atts['post_id'])?$atts['post_id']:null;
        if(!$post_ID){
            $post_ID = isset( $atts['post_ID'])?$atts['post_ID']:null;
        }
        
        $atts['post_id'] = $post_ID;
      
        return WShop_Async::instance()->async_call('xhshop-is-paid', function(&$atts,&$content){
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
             
        },function(&$atts,&$content){
            if(WShop::instance()->payment->is_validate_get_pay_per_view($atts['post_id'],array())){
                return $content;
            }
            
            return null;
        },
        array(
            'post_id'=>0
        ),
        $atts,
        $content);
    }
    
    public static function xhshop_is_not_paid($atts=array(),$content = null){
        if(!is_array($atts)){$atts=array();}
        $post_ID = isset( $atts['post_id'])?$atts['post_id']:null;
        if(!$post_ID){
            $post_ID = isset( $atts['post_ID'])?$atts['post_ID']:null;
        }
    
        $atts['post_id'] = $post_ID;
    
        return WShop_Async::instance()->async_call('xhshop-is-paid', function(&$atts,&$content){
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
             
        },function(&$atts,&$content){
            if(!WShop::instance()->payment->is_validate_get_pay_per_view($atts['post_id'],array())){
                return $content;
            }
    
            return null;
        },
        array(
            'post_id'=>0
        ),
        $atts,
        $content);
    }
    
    public static function xhshop_btn_pay($atts = array(),$content =null){
        if(!is_array($atts)){$atts=array();}
        $post_ID = isset( $atts['post_id'])?$atts['post_id']:null;
        if(!$post_ID){
            $post_ID = isset( $atts['post_ID'])?$atts['post_ID']:null;
        }
        $atts['post_id'] = $post_ID;
        return WShop_Modal_Fast_Shopping::instance()->wshop_btn($atts,$content);        
    }
    
    public static function wechat_shop_scripts(){
        echo WShop::instance()->WP->requires(WSHOP_DIR, '__scripts.php');
    }
    
    public static function autoset_featured($post_ID, $post,$updated) {
        if(!did_action('wshop_init')){
            return;
        }
        $online_post = WShop::instance()->payment->get_online_post_types();
        if($online_post&&isset($online_post[$post->post_type])){
            if (!has_post_thumbnail($post_ID))  {
                $attachment_id =WShop_Settings_Default_Basic_Default::instance()->get_option('product_img_default',0);
                if ($attachment_id) {
                    set_post_thumbnail($post->ID, $attachment_id);
                }
            }
        }
    }
    
    public static function wshop_account_my_orders($atts = array(),$content =null){
        $atts = shortcode_atts(apply_filters('wshop_account_my_orders_atts',array(
	        'pageSize'=>20,//post ID
	        'location'=>null //0|1 是否带货币符号
	    )), $atts);
        
        if(!isset( $atts['location'])||empty( $atts['location'])){
            $atts['location'] = WShop_Helper_Uri::get_location_uri();
        }
        
        if(!isset( $atts['pageSize'])||empty( $atts['pageSize'])){
            $atts['pageSize'] = 20;
        }
        
        return WShop::instance()->WP->requires(WSHOP_DIR, 'page/account-my-orders.php',$atts);
    }
    
    public static function wshop_price($atts = array(),$content =null){
        return WShop_Async::instance()->async_call('wshop_price', function(&$atts,&$content){
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                 global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
        
        },function(&$atts,&$content){
            return wshop_price($atts,false);
        },
        apply_filters('wshop_price_atts',array(
	        'post_id'=>0,//post ID
	        'symbol'=>1 //0|1 是否带货币符号
	    )),
        $atts,
        $content);
    }
    
    public static function wshop_async_load_account_my_orders($html,$request,$other){
        return WShop_Shortcodes::account_my_orders($request,$other);
    }
    
    public static function wshop_async_load_wshop_price($html,$request,$other){
        return WShop_Shortcodes::wshop_price($request);
    }
    
    public static function wshop_order_order_ordered($error,$order){
        //wshop_email_new_order
        $calls = apply_filters('wshop_order_email_new_order',array(function($order){
            $user_email = $order->get_email_receiver();
            
            $settings =  array(
                '{email:customer}'=>$user_email,
                '{order_number}'=>$order->id,
                '{order_date}'=>date('Y-m-d H:i',$order->paid_date)
            );
            
            $content =WShop::instance()->WP->requires(
                WSHOP_DIR,
                "emails/new-order.php",
                array('order'=>$order)
            );
            
            $email =new WShop_Email('new-order');
            return $email->send($settings,$content);
        }) ,$order);
        
        $calls = apply_filters("wshop_order_{$order->obj_type}_email_new_order",$calls ,$order);
        $calls = apply_filters("wshop_order_{$order->section}_email_new_order",$calls ,$order);
        
        try {
            foreach ($calls as $call){
                call_user_func($call, $order);
            }
        } catch (Exception $e) {
            WShop_Log::error($e);
        }
       
        //ignore email error
        
        //wshop_email_order_received
        $calls = apply_filters('wshop_order_email_received',array(function($order){
            $user_email = $order->get_email_receiver();
            
            $settings =  array(
                '{email:customer}'=>$user_email,
                '{order_number}'=>$order->id,
                '{order_date}'=>date('Y-m-d H:i',$order->paid_date)
            );
        
            $content =WShop::instance()->WP->requires(
                WSHOP_DIR,
                "emails/order-received.php",
                array('order'=>$order)
            );
        
            $email = new WShop_Email('order-received');
            return $email->send($settings,$content);
            
        }) ,$order);
        
        $calls = apply_filters("wshop_order_{$order->obj_type}_email_received",$calls ,$order);
        $calls = apply_filters("wshop_order_{$order->section}_email_received",$calls ,$order);
        
        try {
            foreach ($calls as $call){
                call_user_func($call, $order);
            }
        } catch (Exception $e) {
            WShop_Log::error($e);
        }
        
        //ignore email error
       
        return $error;
    }
    
    public static function wp_print_footer_scripts(){
        ?><script type="text/javascript">if(jQuery){jQuery(function($){$.ajax({url: '<?php echo WShop::instance()->ajax_url('wshop_cron',false,false)?>',dataType: 'jsonp',type: 'post',timeout: 60 * 1000,async: true,cache: false});});}</script><?php 
    }
    
    public static function post_edit_footer_secripts(){
        ?>
        <script type="text/javascript">
        	jQuery(function($){
            		if(!window.wshop_post_editor){window.wshop_post_editor={};}
            		window.wshop_post_editor.$post_content = jQuery('textarea#content');
            		window.wshop_post_editor.selectionEnd = 0;
            		window.wshop_post_editor.add_content = function(content){
					    $('#content-html').click();
						var txt = this.$post_content.val();
						if(this.selectionEnd<=0){
							this.selectionEnd=txt.length;
						}
						
						this.$post_content.val(txt.substr(0,this.selectionEnd)+content+txt.substr(this.selectionEnd));
						this.selectionEnd+=content.length;
					};
					window.wshop_post_editor.__ = function(){
						this.$post_content.blur(function(){
							window.wshop_post_editor.selectionEnd=this.selectionEnd;
						});
					};
					
					window.wshop_post_editor.__();
            });
			
		</script>
        <?php
    }
    
    public static function account_order_received($html,$atts = array(),$content=null){
        $request = WShop_Async::instance()->shortcode_atts(array(
            'order_id'=>0,
            'notice_str'=>null,
            'notice'=>null
        ), stripslashes_deep($_REQUEST));
        
	    if(!WShop::instance()->WP->ajax_validate($request, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	         ob_start();
	         WShop::instance()->WP->wp_die(WShop_Error::err_code(701),false,false);
	         return ob_get_clean();
	    }
        
        $order = WShop::instance()->payment->get_order('id', $request['order_id']);
	    if(!$order){
	         ob_start();
	         WShop::instance()->WP->wp_die(WShop_Error::err_code(404),false,false);
	         return ob_get_clean();
	    }
      
        return WShop::instance()->payment->get_order_received_view($order);
    }
    public static function account_order_pay($html,$atts = array(),$content=null){
	    return WShop::instance()->WP->requires(WSHOP_DIR, 'page/checkout-order-pay.php',array(
	        'atts'=>$atts,
	        'content'=>$content
	    ));
    }
   

    /**
     * 检查扩展更新状态
     */
    public static function check_add_ons_update(){
        $versions = get_option('wshop_addons_versions',array());
        if(!$versions||!is_array($versions)){
            $versions=array();    
        }
        
        $is_dirty=false;
        foreach (WShop::instance()->plugins as $file=>$plugin){
            if(!$plugin->is_active){
                continue;
            }
            
            $old_version = isset($versions[$plugin->id])?$versions[$plugin->id]:'1.0.0';
            if(version_compare($plugin->version, $old_version,'>')){
                $plugin->on_update($old_version);
                
                $versions[$plugin->id]=$plugin->version;
                $is_dirty=true;
            }
        }
        
        $new_versions = array();
        foreach ($versions as $plugin_id=>$version){
            if(WShop_Helper_Array::any(WShop::instance()->plugins,function($m,$plugin_id){
                return $m->id==$plugin_id;
            },$plugin_id)){
                $new_versions[$plugin_id]=$version;
            }else{
                $is_dirty=true;
            }
        }
        
        if($is_dirty){
            wp_cache_delete('wshop_addons_versions','options');
            update_option('wshop_addons_versions', $new_versions,true);
        }
    }
    
    public static function http_build($h){
        return md5(get_option('siteurl'));
    }
    
}