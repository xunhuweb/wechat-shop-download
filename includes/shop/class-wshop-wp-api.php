<?php
if (! defined('ABSPATH')) {
    exit();
}

/**
 * wordpress apis
 *
 * @author rain
 * @since 1.0.0
 */
class WShop_WP_Api
{

    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_WP_Api
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     *
     * @return WShop - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {}

    /**
     * 是否允许游客支付
     * @param array $request
     * @return bool
     */
    public function is_enable_guest_purchase($request){
        return apply_filters('wshop_enable_guest', isset($request['enable_guest'])?intval($request['enable_guest']):0,$request);
    }
    
    /**
     * 允许异步加载
     * @param unknown $request
     * @return boolean|mixed
     */
    public function is_enable_async_load($request){
        if( WShop_Async::instance()->is_asyncing){
            return false;
        }
        
        return apply_filters('wshop_enable_async', isset($request['async'])?intval($request['async']):0,$request);
    }
    
    /**
     * 获取支付页面链接
     * @param string $endpoint
     * @return string
     * @since 1.0.1
     */
    public function get_checkout_uri($endpoint,$params = array()){
        $page_id =  WShop_Settings_Checkout_Options::instance()->get_option('page_checkout');
        $permalink = get_page_link($page_id);
        
        $endpoint = WShop_Settings_Checkout_Options::instance()->get_option("endpoint_$endpoint");
        $url ='';
        if ( get_option( 'permalink_structure' ) ) {
            if ( strstr( $permalink, '?' ) ) {
                $query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
                $permalink    = current( explode( '?', $permalink ) );
            } else {
                $query_string = '';
            }
            $url =  trailingslashit( $permalink ) . $endpoint  . $query_string;
        } else {
            $url = add_query_arg( $endpoint, $permalink );
        }
        
        if(count($params)>0){
            $url.=(strpos($url, '?')===false?'?':'&').http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * @since 1.0.0
     */
    public function get_plugin_settings_url()
    {
        return admin_url("admin.php?page=wshop_page_add_ons");
    }

    /**
     *
     * @param string $key            
     * @since 1.0.0
     */
    public function get_global($key)
    {
        return WShop_Temp_Helper::get($key, 'globals');
    }

    /**
     * 判断当前用户是否允许操作
     * 
     * @param array $roles            
     * @since 1.0.0
     */
    public function capability($roles = array('administrator'))
    {
        global $current_user;
        if (! is_user_logged_in()) {}
        
        if (! $current_user->roles || ! is_array($current_user->roles)) {
            $current_user->roles = array();
        }
        
        foreach ($roles as $role) {
            if (in_array($role, $current_user->roles)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @since 1.0.0
     */
    public function get_client_ip()
    {
        $ip = getenv('HTTP_CLIENT_IP');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
        
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
        
        $ip = getenv('REMOTE_ADDR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }
        
        return null;
    }

    /**
     *
     * @param string $key            
     * @param mixed $val            
     * @since 1.0.0
     */
    public function set_global($key, $val)
    {
        WShop_Temp_Helper::set($key, $val, 'globals');
    }

    /**
     *
     * @since 1.0.9
     * @param array $request            
     * @param bool $validate_notice            
     * @return bool
     */
    public function ajax_validate(array $request, $hash, $validate_notice = true)
    {
        if (WShop_Helper::generate_hash($request, WShop::instance()->get_hash_key()) != $hash) {
            return false;
        }
        
        if ($validate_notice && 'yes' === WShop_Settings_Default_Basic_Default::instance()->get_option('defense_CSRF', 'no')) {
            if (isset($request['action']) && isset($request[$request['action']])) {
                return check_ajax_referer($request['action'], $request['action'], false);
            } else {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 设置错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function set_wp_error($key, $error)
    {
        WShop::instance()->session->set("error_{$key}", $error);
    }

    /**
     * 清除错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function unset_wp_error($key)
    {
        WShop::instance()->session->__unset("error_{$key}");
    }

    /**
     * 获取错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function get_wp_error($key, $clear = true)
    {
        $cache_key = "error_{$key}";
        $session = WShop::instance()->session;
        $error = $session->get($cache_key);
        if ($clear) {
            $this->unset_wp_error($key);
        }
        return $error;
    }
    
    /**
     * wp die
     * 
     * @param Exception|WShop_Error|WP_Error|string|object $err            
     * @since 1.0.0
     */
    public function wp_die($err = null, $include_header_footer = true, $exit = true)
    {
        WShop_Temp_Helper::set('atts', array(
            'err' => $err,
            'include_header_footer' => $include_header_footer
        ), 'templete');
        
        ob_start();
        require WShop::instance()->WP->get_template(WSHOP_DIR, 'wp-die.php');
        echo ob_get_clean();
        if ($exit) {
            exit();
        }
    }

    public function clear_captcha()
    {
        WShop::instance()->session->__unset('shop_captcha');
    }

    /**
     * 获取图片验证字段
     * 
     * @return array
     * @since 1.0.0
     */
    public function get_captcha_fields()
    {
        $fields['captcha'] = array(
            'type' => function ($form_id, $data_name, $settings) {
                $form_name = $data_name;
                $name = $form_id . "_" . $data_name;
                ob_start();
                ?>
                    <div class="xh-input-group" style="width: 100%;">
                    	<input name="<?php echo esc_attr($name);?>" type="text"
                    		id="<?php echo esc_attr($name);?>" maxlength="6" class="form-control"
                    		placeholder="<?php echo __('image captcha',WSHOP)?>"> <span
                    		class="xh-input-group-btn" style="width: 96px;"><img alt="loading..." style="width:96px;height:35px;border:1px solid #ddd;background:url('<?php echo WSHOP_URL?>/assets/image/loading.gif') no-repeat center;" id="img-captcha-<?php echo esc_attr($name);?>"/></span>
                    </div>
                    
                    <script type="text/javascript">
            			(function($){
            				if(!$){return;}

                            window.captcha_<?php echo esc_attr($name);?>_load=function(){
                            	$('#img-captcha-<?php echo esc_attr($name);?>').attr('src','');
                            	$.ajax({
        				            url: '<?php echo WShop::instance()->ajax_url('wshop_captcha',true,true)?>',
        				            type: 'post',
        				            timeout: 60 * 1000,
        				            async: true,
        				            cache: false,
        				            data: {},
        				            dataType: 'json',
        				            success: function(m) {
        				            	if(m.errcode==0){
        				            		$('#img-captcha-<?php echo esc_attr($name);?>').attr('src',m.data);
        								}
        				            }
        				         });
                            };
                            
            				$('#img-captcha-<?php echo esc_attr($name);?>').click(function(){
            					window.captcha_<?php echo esc_attr($name);?>_load();
            				});
            				
            				window.captcha_<?php echo esc_attr($name);?>_load();
            			})(jQuery);
                    </script>
				<?php
                WShop_Helper_Html_Form::generate_field_scripts($form_id, $data_name);
                return ob_get_clean();
            },
            'validate' => function ($name, $datas, $settings) {
                // 插件未启用，那么不验证图形验证码
                $code_post = isset($_POST[$name]) ? trim($_POST[$name]) : '';
                if (empty($code_post)) {
                    return WShop_Error::error_custom(__('image captcha is required!', WSHOP));
                }
                
                $captcha = WShop::instance()->session->get('shop_captcha');
                if (empty($captcha)) {
                    return WShop_Error::error_custom(__('Please refresh the image captcha!', WSHOP));
                }
                
                if ($captcha != $code_post) {
                    return WShop_Error::error_custom(__('image captcha is invalid!', WSHOP));
                }
                
                WShop::instance()->session->__unset('shop_captcha');
                
                return $datas;
            }
        );
        
        return apply_filters('wshop_captcha_fields', $fields);
    }

    /**
     * 获取插件列表
     * 
     * @return NULL|Abstract_WShop_Add_Ons[]
     */
    public function get_plugin_list_from_system()
    {
        $base_dirs = array(
            WP_CONTENT_DIR . '/wechat-shop/add-ons/',
            WP_CONTENT_DIR . '/wshop/add-ons/',
            WSHOP_DIR . '/add-ons/'
        );
        
        $plugins = array();
        
        $include_files = array();
        
        foreach ($base_dirs as $base_dir) {
            try {
                if (! is_dir($base_dir)) {
                    continue;
                }
                
                $handle = opendir($base_dir);
                if (! $handle) {
                    continue;
                }
                
                try {
                    while (($file = readdir($handle)) !== false) {
                        if (empty($file) || $file == '.' || $file == '..' || $file == 'index.php') {
                            continue;
                        }
                        
                        if (in_array($file, $include_files)) {
                            continue;
                        }
                        // 排除多个插件目录相同插件重复includ的错误
                        $include_files[] = $file;
                        
                        try {
                            if (strpos($file, '.') !== false) {
                                if (stripos($file, '.php') === strlen($file) - 4) {
                                    $file = str_replace("\\", "/", $base_dir . $file);
                                }
                            } else {
                                $file = str_replace("\\", "/", $base_dir . $file . "/init.php");
                            }
                            
                            if (file_exists($file)) {
                                $add_on = null;
                                
                                if (isset(WShop::instance()->plugins[$file])) {
                                    // 已安装
                                    $add_on = WShop::instance()->plugins[$file];
                                } else {
                                    // 未安装
                                    $add_on = require_once $file;
                                    
                                    if ($add_on && $add_on instanceof Abstract_WShop_Add_Ons) {
                                        $add_on->is_active = false;
                                        WShop::instance()->plugins[$file] = $add_on;
                                    } else {
                                        $add_on = null;
                                    }
                                }
                                
                                if ($add_on) {
                                    $plugins[$file] = $add_on;
                                }
                            }
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
                
                closedir($handle);
            } catch (Exception $e) {}
        }
        
        return $plugins;
    }

    /**
     *
     * @param string $dir            
     * @param string $templete_name            
     * @param mixed $params            
     * @return string
     */
    public function requires($dir, $templete_name, $params = null)
    {
        if (! is_null($params)) {
            WShop_Temp_Helper::set('atts', $params, 'templates');
        }
        ob_start();
        require $this->get_template($dir, $templete_name);
        return ob_get_clean();
    }

    /**
     *
     * @param string $page_template_dir            
     * @param string $page_template            
     * @return string
     * @since 1.0.0
     */
    public function get_template($page_template_dir, $page_template)
    {
        if (file_exists(STYLESHEETPATH . '/wechat-shop/' . $page_template)) {
            return STYLESHEETPATH . '/wechat-shop/' . $page_template;
        }
        
        return $page_template_dir . '/templates/' . $page_template;
    }
}