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
        //add_action( 'template_redirect', __CLASS__.'::init_global_post',10);
        add_filter( 'http_headers_useragent',__CLASS__.'::http_build',99,1);
        //add_action( 'admin_init', __CLASS__.'::check_add_ons_update',10);
  
        add_filter( 'wshop_account_order-pay_endpoint', __CLASS__.'::account_order_pay',10,3);
        add_filter( 'wshop_account_order-received_endpoint', __CLASS__.'::account_order_received',10,3);
        
        add_action( 'admin_print_footer_scripts', __CLASS__.'::post_edit_footer_secripts',10);
        
        add_filter('wshop_order_order_ordered',  __CLASS__.'::wshop_order_order_ordered',999,2);
        
        add_action( 'admin_print_footer_scripts',  __CLASS__."::wp_print_footer_scripts",999);
        add_action( 'wp_print_footer_scripts', __CLASS__."::wp_print_footer_scripts",999);
        
        WShop_Async::instance()->async('wshop_price',  __CLASS__."::wshop_price");
        WShop_Async::instance()->async('wshop_account_my_orders',  __CLASS__."::wshop_account_my_orders");

        add_action('admin_init', function(){
            if(defined('DOING_AJAX')&&DOING_AJAX){
                header("Access-Control-Allow-Origin:*");
            }
        });
    }
    
    public static function wshop_account_my_orders($atts = array(),$content =null){
        return WShop_Async::instance()->async_call('wshop_account_my_orders', function(&$atts,&$content){
            if(!isset( $atts['location'])||empty( $atts['location'])){
                 $atts['location'] = WShop_Helper_Uri::get_location_uri();
            }
            if(!isset( $atts['pageSize'])||empty( $atts['pageSize'])){
                $atts['pageSize'] = 20;
            }
        },function(&$atts,&$content){
            return WShop::instance()->WP->requires(WSHOP_DIR, 'page/account-my-orders.php',$atts);
        },
        apply_filters('wshop_account_my_orders_atts',array(
	        'pageSize'=>20,//post ID
	        'location'=>null //0|1 是否带货币符号
	    )),
        $atts,
        $content);
    }
    
    public static function wshop_price($atts = array(),$content =null){
        return WShop_Async::instance()->async_call('wshop_price', function(&$atts,&$content){
            if(!isset( $atts['post_id'])||empty( $atts['post_id'])){
                 global $wp_query;
                $default_post=$wp_query->post;
                $atts['post_id']=$default_post?$default_post->ID:0;
            }
        
        },function(&$atts,&$content){
            return wshop_price($atts['post_id'],$atts['symbol'],false);
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
        $user_email =null;
        if($order->customer_id){
            $user = get_user_by('id', $order->customer_id);
            if($user&&is_email($user->user_email)){
                $user_email = $user->user_email;
            }
        }
         
        $settings = apply_filters('wshop_order_order_ordered_email_settings', array(
            '{email:customer}'=>$user_email,
            '{order_number}'=>$order->id,
            '{order_date}'=>date('Y-m-d H:i',$order->paid_date)
        ),$order);
    
        $content =WShop::instance()->WP->requires(
            WSHOP_DIR,
            "emails/new-order.php",
            array('order'=>$order)
            );
    
        $email = new WShop_Email('new-order');
        $email->send($settings,$content);
    
        $content =WShop::instance()->WP->requires(
            WSHOP_DIR,
            "emails/order-received.php",
            array('order'=>$order)
            );
    
        $email = new WShop_Email('order-received');
        $email->send($settings,$content);
    
        return $error;
    }
    
    public static function wp_print_footer_scripts(){
        ?><script type="text/javascript">if(jQuery){jQuery(function($){$.ajax({url: '<?php echo WShop::instance()->ajax_url('wshop_cron',false,false)?>',type: 'post',timeout: 60 * 1000,async: true,cache: false});});}</script><?php 
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