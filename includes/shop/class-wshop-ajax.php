<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WShop_Ajax class
 *
 * @version     2.1.0
 * @category    Class
 */
class WShop_Ajax {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    'wshop_cron'   =>__CLASS__ . '::cron',
		    'wshop_async_load'   =>__CLASS__ . '::async_load',
		    'wshop_checkout'=>__CLASS__ . '::checkstand',
		    'wshop_plugin'=>__CLASS__ . '::plugin',
		    'wshop_service'=>__CLASS__ . '::service',
		    'wshop_captcha'=>__CLASS__ . '::captcha',
		    'wshop_obj_search'=>__CLASS__. '::obj_search',
		    'wshop_update_order'=>__CLASS__.'::update_order',
		    'wshop_order_note'=>__CLASS__.'::order_note',
		);
		
		$add_ons = WShop::instance()->get_available_addons();
		if($add_ons){
		    foreach ($add_ons as $add_on){
		        $shortcodes["wshop_{$add_on->id}"] =array($add_on,'do_ajax');
		    }
		}
		$shortcodes = apply_filters('wshop_ajax', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
		    add_action ( "wp_ajax_$shortcode",        $function);
		    add_action ( "wp_ajax_nopriv_$shortcode", $function);
		}
	}

	public static function async_load(){
	    $action ='wshop_async_load';
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        $action=>null,
	        'hook'=>null,
	        'atts'=>null,
	        'content'=>null
	    ), stripslashes_deep($_REQUEST));
	    
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo WShop_Error::err_code(701)->to_json();
	        exit;
	    }
	    
	    $atts =json_decode($params['atts'],true);
	    $api = WShop_Async::instance();
	    $api->is_asyncing=true;
	    $api->async_atts = $atts;
	    
	    echo WShop_Error::success(apply_filters("wshop_async_load_{$params['hook']}",$atts,$params['content']))->to_json();
	    exit;
	}
	
	//插件定时服务
	public static function cron(){
	    header("Access-Control-Allow-Origin:*");
	    $last_execute_time = intval(get_option('wshop_cron',0));
	    $now = time();
	    
	    //间隔30秒
	    $step =$last_execute_time-($now-60);
	    if($step>0){
	       echo 'next step:'.$step;
	       exit;
	    }
	    
	    update_option('wshop_cron',$now,false);
	    
	   try {
	       do_action('wshop_cron');
	   } catch (Exception $e) {
	       WShop_Log::error($e);
	       //ignore
	   }
	    
	    //清楚session 数据
	    WShop::instance()->session->cleanup_sessions();
	    WShop_Hooks::check_add_ons_update();
	    
	    echo 'hello wshop cron';
	    exit;
	}

	/**
	 * @since 1.0.0 更新订单
	 */
	public static function order_note(){
	    if(!WShop::instance()->WP->capability()){
	        echo (WShop_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action ='wshop_order_note';
	    $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null,
            'tab'=>null
        ), stripslashes_deep($_REQUEST));
	
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo WShop_Error::err_code(701)->to_json();
	        exit;
	    }
	     
	    global $wpdb;
	    switch ($params['tab']){
	        case 'remove':
	           $note = new WShop_Order_Note(sanitize_key($_POST['id']));
	           if(!$note->is_load()||$note->order_id!=sanitize_key($_POST['order_id'])){
	               echo WShop_Error::err_code(404)->to_json();
	               exit;
	           }
	         
	           echo $note->remove()->to_json();
	           exit;
	        case 'add':
	            $order = WShop::instance()->payment->get_order('id', stripslashes($_POST['order_id']));
	            if(!$order){
	                echo WShop_Error::err_code(404)->to_json();
	                exit;
	            }
	            
	            $note =new WShop_Order_Note(array(
	               'content'=>stripslashes($_POST['content']),
	               'created_date'=>current_time( 'timestamp' ),
	               'note_type'=>sanitize_key($_POST['note_type']),
	               'user_id'=>get_current_user_id(),
	               'order_id'=>$order->id
	            ));
	            
	            if(!in_array($note['note_type'], array_keys(WShop_Order_Note::get_note_types()))){
	                echo WShop_Error::err_code(404)->to_json();
	                exit;
	            }
	            
	            echo $note->insert()->to_json();
	            exit;
	    }
	}
	
	/**
	 * @since 1.0.0 更新订单
	 */
	public static function update_order(){
	    if(!WShop::instance()->WP->capability()){
	        echo (WShop_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action ='wshop_update_order';
	    $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null,
            'tab'=>null
        ), stripslashes_deep($_REQUEST));
	     
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo WShop_Error::err_code(701)->to_json();
	        exit;
	    }
	    
	    $error = WShop_Order_Helper::update_order( isset($_REQUEST['id'])?sanitize_key($_REQUEST['id']):0, $params['tab']);
	    echo $error->to_json();
	    exit;
	}
	
	/**
	 * @since 1.0.0 查询用户
	 */
	public static function obj_search(){
	    $action ='wshop_obj_search';
	   $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null
        ), stripslashes_deep($_REQUEST));
	    
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo WShop_Error::err_code(701)->to_json();
	        exit;
	    }
	    
	    $keywords = isset($_REQUEST['term'])?stripslashes($_REQUEST['term']):null;
	 
	    $type = isset($_REQUEST['obj_type'])?sanitize_key($_REQUEST['obj_type']):null;
	    if(empty($keywords)||empty($type)){
	        echo json_encode(array(
	            'items'=>null
	        ));
	        exit;
	    }
	    
	    $len =mb_strlen($keywords);
	    if($len<1||$len>=15){
	        echo json_encode(array(
	            'items'=>null
	        ));
	        exit;
	    }
	    global $wpdb;
	  
	   switch ($type){
	       case 'customer':
	           $users = $wpdb->get_results($wpdb->prepare(
	               "select u.ID,
        	               u.user_login,
        	               u.user_email
	               from {$wpdb->prefix}users u
	               where (u.user_login like %s or u.user_email like %s)
	               limit 10;", "$keywords%","$keywords%"));
	            
	           $results = array();
	           if($users){
	               foreach ($users as $user){
	                   if(!empty($user->user_email)){
	                       $results[]=array(
	                           'id'=>$user->ID,
	                           'text'=>"{$user->user_login}({$user->user_email})"
	                       );
	                   }else{
	                       $results[]=array(
	                           'id'=>$user->ID,
	                           'text'=>"{$user->user_login}"
	                       );
	                   }
	               }
	           }
	            
	           echo json_encode(array(
	               'items'=>$results
	           ));
	           exit;
	       case 'product':
	           global $wpdb;
	           $post_types = WShop::instance()->payment->get_online_post_types();
	           $sql ="";
	           if(count($post_types)>0){
	               $sql.=" and u.post_type in (";
	               $index=0;
	               foreach ($post_types as $type=>$att){
	                   if($index++!=0){
	                       $sql+=",";
	                   }
	                   $sql.="'{$type}'";
	               }
	               $sql.=")";
	           }
	           
	           $posts = $wpdb->get_results($wpdb->prepare(
	               "select u.ID,
	                       u.post_title
	               from {$wpdb->prefix}posts u
	               where (u.post_title like %s)
	                     $sql
	               limit 10;", "$keywords%"));
	      
	           $results = array();
	           if($posts){
	               foreach ($posts as $post){
	                   $results[]=array(
	                       'id'=>$post->ID,
	                       'text'=>$post->post_title
	                   );
	               }
	           }
	           
	           echo json_encode(array(
	               'items'=>$results
	           ));
	           exit;
	       default:
	           
	           $posts = $wpdb->get_results($wpdb->prepare(
	               "select u.ID,
	                       u.post_title
	               from {$wpdb->prefix}posts u
	               where (u.post_title like %s)
	                      and u.post_type=%s
	               limit 10;","$keywords%", $type));
	               $results = array();
	               if($posts){
	                   foreach ($posts as $post){
	                       $results[]=array(
	                           'id'=>$post->ID,
	                           'text'=>$post->post_title
	                       );
	                   }
	               }
	           
	               echo json_encode(array(
	                   'items'=>$results
	               ));
	               exit;
	   }
	}

	/**
	 * 验证码
	 * @since 1.0.0
	 */
	public static function captcha(){
	    require_once WSHOP_DIR.'/includes/captcha/CaptchaBuilderInterface.php';
	    require_once WSHOP_DIR.'/includes/captcha/PhraseBuilderInterface.php';
	    require_once WSHOP_DIR.'/includes/captcha/CaptchaBuilder.php';
	    require_once WSHOP_DIR.'/includes/captcha/PhraseBuilder.php';
	   
	    $action ='wshop_captcha';
	   $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null
        ), stripslashes_deep($_REQUEST));
	    
	   $hash=WShop_Helper::generate_hash($params, WShop::instance()->get_hash_key());
	   if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
           WShop::instance()->WP->wp_die(WShop_Error::err_code(701));
           exit;
	   }
	    
	    // header('Content-type: image/jpeg');
	    $builder = Gregwar\Captcha\CaptchaBuilder::create() ->build();
	    WShop::instance()->session->set('shop_captcha', $builder->getPhrase());
	    
	    echo WShop_Error::success($builder ->inline())->to_json();
	    exit;
	}
	
	
	public static function checkstand(){   
	    $action ='wshop_checkout';
	    $tab = isset($_REQUEST['tab'])?stripslashes($_REQUEST['tab']):null;

	    switch ($tab){
	        case 'update_shopping_cart':
	            $request = shortcode_atts(array_merge(
	                WShop::instance()->payment->pay_atts(),
	                array(
	                    'cart_id'=>0
	                )
	                ), stripslashes_deep($_REQUEST));
	             
	            $datas=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null,
	                'subtab'=>null
	            ), stripslashes_deep($_REQUEST));
	             
	            if(!WShop::instance()->WP->ajax_validate(array_merge($request,$datas), isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                echo WShop_Error::err_code(701)->to_json();
	                exit;
	            }
	            
	            $cart = new WShop_Shopping_Cart($request['cart_id']);
	            if(!$cart->is_load()){
	                //购物车信息异常，要求用户刷新页面
	                echo WShop_Error::err_code(201)->to_json();
	                exit;
	            }
	            
	            $post_id = isset($_REQUEST['post_id'])?$_REQUEST['post_id']:null;
	            $product_id=null;
	            foreach ($cart->items as $pid=>$att){
	                if($post_id==$pid){
	                    $product_id = $pid;
	                    break;
	                }
	            }
	            
	            if(!$product_id){
	                //购物车信息异常，要求用户刷新页面
	                echo WShop_Error::err_code(201)->to_json();
	                exit;
	            }
	           
	            switch ($datas['subtab']){
	                case 'change_qty':
	                    $new_qty = isset($_REQUEST['qty'])? intval($_REQUEST['qty']):1;
	                    $error = apply_filters('wshop_shopping_cart_change_qty', WShop_Error::success(),$new_qty,$product_id,$cart);
	                    if(!WShop_Error::is_valid($error)){
	                        echo $error->to_json();
	                        exit;
	                    }
	                    if($new_qty<1){
	                        $new_qty=1;
	                    }
	                    
	                    $cart->items[$product_id]['qty']=$new_qty;
	                   
	                    echo $cart->update(array(
	                        'items'=>maybe_serialize($cart->items)
	                    ))->to_json();
	                    exit;
	                    
	                case 'remove_item':
	                    $error = apply_filters('wshop_shopping_cart_remove_item', WShop_Error::success(),$product_id,$cart);
	                    if(!WShop_Error::is_valid($error)){
	                        echo $error->to_json();
	                        exit;
	                    }
	                    
	                    unset($cart->items[$product_id]);
	                    
	                    echo $cart->update(array(
	                        'items'=>maybe_serialize($cart->items)
	                    ))->to_json();
	                    exit;
	            }
	            
	        case 'fast_shopping':
	            $request = shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array('post_id'=>0)), stripslashes_deep($_REQUEST));
	            $datas=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null
	            ), stripslashes_deep($_REQUEST));
	             
	            if(!WShop::instance()->WP->ajax_validate(array_merge($request,$datas), isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                echo WShop_Error::err_code(701)->to_json();
	                exit;
	            }
	          
	            if(!is_user_logged_in()&&!WShop::instance()->WP->is_enable_guest_purchase($request)){
	               
	                echo WShop_Error::err_code(501)->to_json();
	                exit;
	            }
	            
	            $shopping_cart = WShop::instance()->payment->add_to_cart($request['post_id']);
	            if($shopping_cart instanceof WShop_Error){
	                echo $shopping_cart->to_json();
	                exit;
	            }
	            
	            $request['cart_id']=$shopping_cart->id;
	            $order = WShop::instance()->payment->pay($request);
	            if($order instanceof WShop_Error){
	                echo $order->to_json();
	                exit;
	            }
	            
	            echo WShop_Error::success($order->get_pay_url())->to_json();
	            exit;
	            break;
	        case 'add_to_cart':
	            $request = shortcode_atts(array_merge(WShop::instance()->payment->pay_atts(),array('post_id'=>0)), stripslashes_deep($_REQUEST));
	            $datas=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null
	            ), stripslashes_deep($_REQUEST));
	          
	            if(!WShop::instance()->WP->ajax_validate(array_merge($request,$datas), isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                WShop::instance()->WP->wp_die(WShop_Error::err_code(701));
	                exit;
	            }
	            
	            if(!is_user_logged_in()&&!WShop::instance()->WP->is_enable_guest_purchase($request)){
	                if(empty($request['location'])){$request['location']=home_url('/');}
	                wp_redirect(wp_login_url($request['location']));
	                exit;
	            }
	            
	            $shopping_cart = WShop::instance()->payment->add_to_cart($request['post_id']);
	            if($shopping_cart instanceof WShop_Error){
	                WShop::instance()->WP->wp_die($shopping_cart);
	                exit;
	            }
	            
	            $request = shortcode_atts(WShop::instance()->payment->pay_atts(), stripslashes_deep($_REQUEST));
	            $request['cart_id']=$shopping_cart->id;
	            $request = WShop::instance()->generate_request_params($request,'action');
	            
	            $params = array();
	            $checkout_uri = WShop_Helper_Uri::get_uri_without_params(WShop::instance()->payment->get_order_checkout_url(),$params);
	            
	            wp_redirect($checkout_uri."?".http_build_query(array_merge($request,$params)));
	            exit;
	        case 'create_order':
	            $request = shortcode_atts(array_merge(
    	            WShop::instance()->payment->pay_atts(),
    	            array(
    	               'cart_id'=>0
    	            )
	            ), stripslashes_deep($_REQUEST));
	            
	            $datas=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null
	            ), stripslashes_deep($_REQUEST));
	          
	            if(!WShop::instance()->WP->ajax_validate(array_merge($request,$datas), isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                echo WShop_Error::err_code(701)->to_json();
	                exit;
	            }
	            
	            if(!is_user_logged_in()&&!apply_filters('wshop_enable_guest', $request['enable_guest'],$request)){
	                echo WShop_Error::err_code(501)->to_json();
	                exit;
	            }
	           
	            $order = WShop::instance()->payment->pay($request);
	            
	            if($order instanceof WShop_Error){
	                echo $order->to_json();
	                exit;
	            }
	            
	            echo WShop_Error::success($order->get_pay_url())->to_json();
	            exit;
	        case 'pay': 
	            $params=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null,
	                'order_id'=>null
	            ), stripslashes_deep($_REQUEST));
	            
	            if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                WShop::instance()->WP->wp_die(WShop_Error::err_code(701));
	                exit;
	            }
	            
	            $order =  WShop::instance()->payment->get_order('id', $params['order_id']);
	            if(!$order){
	                WShop::instance()->WP->wp_die(WShop_Error::err_code(404));
	                exit;
	            }
	           
	            $error = apply_filters('wshop_order_pre_process_payment', WShop_Error::success(),$order);
	            if(!WShop_Error::is_valid($error)){
	                WShop::instance()->WP->wp_die($error);
	                exit;
	            }
	            if($order->get_total_amount(false)<=0){
	                $order->sn = $order->generate_sn();
	                $error = $order->complete_payment(null);
	                if(!WShop_Error::is_valid($error)){
	                    WShop::instance()->WP->wp_die($error);
	                    exit;
	                }
	                
	                wp_redirect($order->get_received_url());
	                exit;
	            }
	            
	            $payment_gateway = $order->get_payment_gateway();
	            if(!$payment_gateway){
	                WShop::instance()->WP->wp_die(WShop_Error::error_custom(__('Payment gateway is invalid!',WSHOP)));
	                exit;
	            }
	            
	            $error = $payment_gateway->process_payment($order);
	            if(!WShop_Error::is_valid($error)){
	                WShop::instance()->WP->wp_die($error);
	                exit;
	            }
	            
	            wp_redirect($error->data);
	            exit;
	        case 'is_paid':
	            $params=shortcode_atts(array(
	                'notice_str'=>null,
	                'action'=>$action,
	                $action=>null,
	                'tab'=>null,
	                'order_id'=>null
	            ), stripslashes_deep($_REQUEST));
	            if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	                echo WShop_Error::err_code(701)->to_json();
	                exit;
	            }
	            
	            $order =  WShop::instance()->payment->get_order('id', $params['order_id']);
	            if(!$order){
	                echo WShop_Error::err_code(404)->to_json();
	                exit;
	            }
	            
	            if($order->is_paid()){
	                echo WShop_Error::success($order->get_received_url())->to_json();
	                exit;
	            }
	            
	            echo WShop_Error::error_custom(__('Unpaid!',WSHOP))->to_json();
	            exit;
	            break;
	    }
	}
	
	/**
	 * 远程服务
	 */
	public static function service(){
	   if(!WShop::instance()->WP->capability()){
	        echo (WShop_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action ='wshop_service';
	    $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null,
            'tab'=>null
        ), stripslashes_deep($_REQUEST));
	    
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo (WShop_Error::err_code(701)->to_json());
	        exit;
	    }
	   
	    switch ($params['tab']){

	        //第三方扩展
	        case 'extensions':
	            $page_index = isset($_REQUEST['pageIndex'])?intval($_REQUEST['pageIndex']):1;
	            if($page_index<1){
	                $page_index=1;
	            }
	             
	            $keywords = isset($_REQUEST['keywords'])?sanitize_title_for_query($_REQUEST['keywords']):'';
	             
	            if(empty($keywords)){
	                $info = get_option('wshop-ajax:service:extensions:'.$page_index);
	                if(!$info||!is_array($info)){
	                    $info = array();
	                }
	                
	                if(isset($info['last_cache_time'])&&$info['last_cache_time']>time()){
	                    echo WShop_Error::success($info)->to_json();
	                    exit;
	                }
	            }
	            
	            $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-v3.php';
	            $params = array();
	            
	            $params['pageIndex']=$page_index;
	            $params['keywords']=$keywords;
	            $params['action']='extensions';
	            $params['license_id'] =WShop::$license_id[0];
	            
	            $request =wp_remote_post($api,array(
	                'timeout'=>10,
	                'body'=>$params
	            ));
	             
	            if(is_wp_error( $request )){
	                echo (WShop_Error::err_code(1000)->to_json());
	                exit;
	            }
	      
	            $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	            if(!$info||!is_array($info)){
	                echo (WShop_Error::err_code(1000)->to_json());
	                exit;
	            } 
	            if(empty($keywords)){
    	            $info['last_cache_time'] =time()+24*60*60;
    	            wp_cache_delete('wshop-ajax:service:extensions:'.$page_index,'options');
    	            update_option('wshop-ajax:service:extensions:'.$page_index,$info,false);
	            }
	            echo (WShop_Error::success($info)->to_json());

	            exit;
	        case 'plugins':
	            $page_index = isset($_REQUEST['pageIndex'])?intval($_REQUEST['pageIndex']):1;
	            if($page_index<1){
	                $page_index=1;
	            }
	            $category_id=isset($_REQUEST['category_id'])?intval($_REQUEST['category_id']):0;
	            $keywords = isset($_REQUEST['keywords'])?sanitize_title_for_query($_REQUEST['keywords']):'';
	            if(empty($keywords)){
	                $info = get_option("wshop-ajax:service:plugins:{$category_id}:{$page_index}");
	                if(!$info||!is_array($info)){
	                    $info = array();
	                }
	                 
	                if(isset($info['last_cache_time'])&&$info['last_cache_time']>time()){
	                    echo WShop_Error::success($info)->to_json();
	                    exit;
	                }
	            }
	            $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-v3.php';
	            $params = array();
	             
	            $params['pageIndex']=$page_index;
	            $params['keywords']=$keywords;
	            $params['action']='plugins';
	            $params['category_id'] =$category_id;
	            
	            $request =wp_remote_post($api,array(
	                'timeout'=>10,
	                'body'=>$params
	            ));
	            
	            if(is_wp_error( $request )){
	                echo (WShop_Error::err_code(1000)->to_json());
	                exit;
	            }
	            
	            $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	            if(!$info||!is_array($info)){
	                echo (WShop_Error::err_code(1000)->to_json());
	                exit;
	            }
	            if(empty($keywords)){
    	            $info['last_cache_time'] =time()+24*60*60;
    	            wp_cache_delete("wshop-ajax:service:plugins:{$category_id}:{$page_index}",'options');
    	            update_option("wshop-ajax:service:plugins:{$category_id}:{$page_index}",$info,false);
	            }
	            echo (WShop_Error::success($info)->to_json());
	            
	            exit;
	    }
	}

	/**
	 * 管理员对插件的操作
	 */
	public static function plugin(){
	    error_reporting(E_ALL); ini_set('display_errors', '1');
	    if(!WShop::instance()->WP->capability()){
	        echo (WShop_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action='wshop_plugin';
	  
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        $action=>null,
	        'plugin_id'=>null,
	        'tab'=>null
	    ), stripslashes_deep($_REQUEST));
	    if(!WShop::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo (WShop_Error::err_code(701)->to_json());
	        exit;
	    }
	    
	    $plugins =WShop::instance()->WP->get_plugin_list_from_system();
	    if(!$plugins){
	        echo (WShop_Error::err_code(404)->to_json());
	        exit;
	    }
	    
	    $add_on =null;
	    $add_on_file='';
	    foreach ($plugins as $file=>$plugin){
	        if($plugin->id==$params['plugin_id']){
	            $add_on_file = $file;
	            $add_on=$plugin;
	            break;
	        }
	    }
	    
        if(!$add_on){
            echo (WShop_Error::err_code(404)->to_json());
            exit;
        }
       
	    $cache_time = 2*60*60; 
	    switch ($params['tab']){
	        //插件安装
	        case 'install':
	            $installed = get_option('wshop_plugins_installed',array());
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            $has = false;
	            foreach ($installed as $item){
	                if($item==$add_on_file){
	                    $has=true;break;
	                }
	            }
	           
	            if(!$has){
	                $installed[]=$add_on_file;
	                
	                try {
	                    if($add_on->depends){
	                        foreach ($add_on->depends as $id=> $depend){
	                           $contains = false;
	                           foreach (WShop::instance()->plugins as $plugin){
	                               if(!$plugin->is_active){
	                                   continue;
	                               }
	                               
	                               if($plugin->id==$id){
	                                   $contains=true;
	                                   break;
	                               }
	                           }
	                           
	                           if(!$contains){//依赖第三方插件
	                               echo (WShop_Error::error_custom(sprintf(__('Current add-on is relies on %s!',WSHOP),"“{$depend['title']}”"))->to_json());
	                               exit;
	                           }
	                        }
	                    }
	                    
	                    if(!empty($add_on->min_core_version)){
    	                    if(version_compare(WShop::instance()->version,$add_on->min_core_version, '<')){
    	                        echo (WShop_Error::error_custom(sprintf(__('Core version must greater than or equal to %s!',WSHOP),$add_on->min_core_version))->to_json());
    	                        exit;
    	                    }
	                    }
	                    
	                    $add_on->on_load();
	                    $add_on->on_install(); 
	                    ini_set('memory_limit','128M');
	                    do_action('wshop_flush_rewrite_rules');
                        flush_rewrite_rules();
	                } catch (Exception $e) {
	                    echo (WShop_Error::error_custom($e)->to_json());
	                    exit;
	                }
	               
	            }
	           
	            $plugins_find = WShop::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	             
	            $options = array();
	            foreach ($installed as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	           wp_cache_delete("wshop_plugins_installed",'options');
	           update_option('wshop_plugins_installed', $options,true);
	           
	           echo (WShop_Error::success()->to_json());
	           exit;
	        //插件卸载   
	        case 'uninstall':
	            $installed = get_option('wshop_plugins_installed',array());
	         
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            
	            $new_values = array();
	            foreach ($installed as $item){
	                if($item!=$add_on_file){
	                    $new_values[]=$item;
	                }
	            }
	           
	            try {
	                foreach (WShop::instance()->plugins as $plugin){
	                    if(!$plugin->is_active){
	                        continue;
	                    }
	                    
	                    if(!$plugin->depends){
	                        continue;
	                    }
	                    
	                    foreach ($plugin->depends as $id=>$depend){
	                        if($id==$add_on->id){
	                            echo (WShop_Error::error_custom(sprintf(__('"%s" is relies on current add-on!',WSHOP),"“{$plugin->title}”"))->to_json());
	                            exit;
	                        }
	                    }
	                }
	                
	                $add_on->on_uninstall();
	            } catch (Exception $e) {
	                echo (WShop_Error::error_custom($e)->to_json());
	                exit;
	            }
	            
	            $plugins_find = WShop::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	            
	            $options = array();
	            foreach ($new_values as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	            wp_cache_delete('wshop_plugins_installed', 'options');
	            $update =update_option('wshop_plugins_installed', $options,true);
	            echo (WShop_Error::success()->to_json());
	            exit;
	        //插件更新
	        case 'update':
	        case 'update_admin_options':
	        case 'update_plugin_list':
	           $info =get_option("wshop-ajax:plugin:update:{$add_on->id}");
	           if(!$info||!is_array($info)){
	               $info=array();
	           }
	           
	           if(!isset($info['_last_cache_time'])||$info['_last_cache_time']<time()){
	               $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-add-ons.php';
	               $params = array(
	                   'l'=>$add_on->id,
	                   's'=>get_option('siteurl'),
	                   'v'=>$add_on->version,
	                   'a'=>'update'
	               );
	               //插件为非授权插件
	               $license =null;
	                $info =WShop_Install::instance()->get_plugin_options();
	                if($info){
	                    if(isset($info[$add_on->id])){
	                        $license=$info[$add_on->id];
	                    }
	                    
	                    if(empty($license)){
	                        $license = isset($info['license'])?$info['license']:null;
	                    }
	                }
	                if(empty($license)){
	                    echo WShop_Error::error_unknow()->to_json();
	                    exit;
	                }
	                
	               $params['c']=$license;
	                
	               $request =wp_remote_post($api,array(
	                   'timeout'=>10,
	                   'body'=>$params
	               ));
	              
	               if(is_wp_error( $request )){
	                   echo (WShop_Error::error_custom($request)->to_json());
	                   exit;
	               }
	               
	               $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	               if(!$info||!is_array($info)){
	                   echo (WShop_Error::error_unknow()->to_json());
	                   exit;
	               }
	               
	               //缓存30分钟
	               $info['_last_cache_time'] = time()+$cache_time;
	               update_option("wshop-ajax:plugin:update:{$add_on->id}", $info,false);
	           }
	            
	           $msg =WShop_Error::success();
	           switch($params['tab']){
	               case 'update_admin_options':
	                   $txt =sprintf(__('There is a new version of %s - %s. <a href="%s" target="_blank">View version %s details</a> or <a href="%s" target="_blank">download now</a>.',WSHOP),
	                       $info['name'],
	                       $info['upgrade_notice'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link']
	                       );
	                   $msg = new WShop_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break;
	               case 'update_plugin_list':
	                   $txt =sprintf(__('<tr class="plugin-update-tr active">
	                       <td colspan="3" class="plugin-update colspanchange">
	                       <div class="notice inline notice-warning notice-alt">
	                       <p>There is a new version of %s available.<a href="%s"> View version %s details</a> or <a href="%s" class="update-link">download now</a>.</p>
	                       <div class="">%s</div>
	                       </div></td></tr>',WSHOP),
	                       $info['name'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link'],
	                       $info['upgrade_notice']
	                   );
	                   $msg = new WShop_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break; 
	           }
	           
	           echo $msg->to_json();
	           exit;
	    }
	}
}
