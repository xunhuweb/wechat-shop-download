<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WShop_Async{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * 标记当前请求是否正在执行ajax
     * @var boolean
     */
    public $is_asyncing=false;
    
    /**
     * ajax 异步请求的参数
     * @var array
     */
    public $async_atts = array();
    
    /**
     * Instance
     * @since  1.0.0
     * @return WShop_Async
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    }
    
    /**
     * Constructor for the query class. Hooks in methods.
     *
     * @access public
     */
    private function __construct() {
        
    }
   
    /**
     * 合并请求参数
     * @param array $request 短代码参数
     * @param array $defaults 系统默认参数
     * @since 1.0.1
     * @return array
     */
    public function shortcode_atts($defaults,$request){
        if(!$request||!is_array($request)){
            $request=array();
        }
        
        if($this->is_asyncing){
            $new_atts =shortcode_atts($defaults,$this->async_atts);
        }else{
            $new_atts = $defaults;
        }
        
        return shortcode_atts($new_atts,$request);
    }
    
    public  function async($shortcode,$do_action){
        $shortcode = apply_filters( "wshop_shortcode_{$shortcode}", $shortcode );
        add_shortcode( $shortcode, $do_action );
        add_filter( "wshop_async_load_{$shortcode}", $do_action ,10,2);
    }
    
    public  function async_call($shortcode,$before,$after,$default_atts, &$atts,&$content){
        if(!$atts||!is_array($atts)){$atts=array();}
         
        $atts =$this->shortcode_atts($default_atts,$atts);
        if(!WShop_Async::instance()->is_asyncing){
            $before($atts,$content);
             
            if('yes'===WShop_Settings_Default_Basic_Default::instance()->get_option('enable_async')){
                return WShop_Async::instance()->scripts($shortcode,$atts,$content);
            }
        }
       
        return $after($atts, $content);
    }
    
    /**
     * async html
     * @param string $hook
     * @param array $request
     * @since 1.0.1
     */
    public function scripts($hook,$atts=array(),$content=null){
        $async_context = strtolower(WShop_Helper_String::guid());
        $async_request = array(
            'action'=>'wshop_async_load',
            'hook'=>$hook,
            'atts'=>json_encode($atts),
            'content'=>$content
        );
        ob_start();
        ?> <div id="wshop-async-<?php echo $async_context; ?>"><script type="text/javascript">if(jQuery){jQuery(function($){var data = <?php echo json_encode(WShop::instance()->generate_request_params($async_request,$async_request['action']))?>;$.ajax({url: '<?php echo WShop::instance()->ajax_url()?>',type: 'post',timeout: 60 * 1000,async: true,cache: false,data: data,beforeSend:function(){var $handler =$('#wshop-async-<?php echo $async_context; ?>');if(typeof $handler.loading=='function'){$handler.loading();}}, dataType: 'json',success: function(m) {var $handler =$('#wshop-async-<?php echo $async_context; ?>');if(typeof $handler.loading=='function'){$handler.loading('hide');}if(m.errcode!=0){console.error(m.errmsg);return;}$handler.html(m.data);},error:function(e){var $handler =$('#wshop-async-<?php echo $async_context; ?>');if(typeof $handler.loading=='function'){$handler.loading('hide');}$handler.remove();console.error(e.responseText);}});});}</script></div><?php 
        return ob_get_clean();
    }
}