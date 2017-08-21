<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WShop_Page{
    private static $page_templates=array();
    
    public static function init(){
        add_filter( 'theme_page_templates',__CLASS__.'::theme_page_templates',10,4);
        add_filter( 'template_include', __CLASS__.'::template_include' ,10,1);
        
        //template must be start with shop.
        $templates = apply_filters('wshop_page_templates', array(
            WSHOP_DIR=>array(
                'page/checkout.php'=>__('WShop - Checkout',WSHOP),
            )
        ));
        
        foreach ($templates as $dir=>$template_list){
            self::$page_templates[$dir]=$template_list;
        }
    }
    
    public static function init_page(){
        self::init_page_checkout();
    }
    
    private static function init_page_checkout(){
        $api =WShop_Settings_Checkout_Options::instance();
        $page_id =intval($api->get_option('page_checkout',0));
        $page=null;
        if($page_id>0){
            return true;
        }
    
        $page_id =wp_insert_post(array(
            'post_type'=>'page',
            'post_name'=>'checkout',
            'post_title'=>__('WShop - Checkout',WSHOP),
            'post_content'=>'[wshop_page_checkout]',
            'post_status'=>'publish',
            'meta_input'=>array(
                '_wp_page_template'=>'page/checkout.php'
            )
        ),true);
    
        if(is_wp_error($page_id)){
            WShop_Log::error($page_id);
            throw new Exception($page_id->get_error_message());
        }
    
        $api->update_option('page_checkout', $page_id,true);
        return true;
    }
    

    /**
     * rewrite page templates
     * @param string $template
     * @return string
     */
    public static function template_include($template){
        global $post;
        if(!$post||$post->post_type!='page'){
            return $template;
        }
         
        $page_template = get_page_template_slug($post);
        if(empty($page_template)){
            return $template;
        }
         
        if($page_template==$template){
            return $template;
        }
         
        //加载插件默认模板
        foreach ( self::$page_templates as $dir=>$templates){
            foreach ($templates as $ltemplate=>$name){
                if($page_template==$ltemplate){
                    if(file_exists(STYLESHEETPATH.'/wechat-shop/'.$page_template)){
                        return STYLESHEETPATH.'/wechat-shop/'.$page_template;
                    }
    
                    $file = $dir.'/templates/'.$ltemplate;
                    if(file_exists($file)){
                        return $file;
                    }
                }
            }
        }
    
        return $template;
    }
    
    /**
     * Filters list of page templates for a theme.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type.
     *
     * @since 1.0.0
     *
     * @param array        $post_templates Array of page templates. Keys are filenames,
     *                                     values are translated names.
     * @param WP_Theme     $WP_Theme           The theme object.
     * @param WP_Post|null $post           The post being edited, provided for context, or null.
     * @param string       $post_type      Post type to get the templates for.
     */
    public static function theme_page_templates($post_templates, $WP_Theme, $post, $post_type=null){
        foreach ( self::$page_templates as $dir=>$templates){
            foreach ($templates as $template=>$template_name){
                if(!isset($post_templates[$template])){
                    $post_templates[$template] =$template_name;
                }
            }
        }
        return $post_templates;
    }
}