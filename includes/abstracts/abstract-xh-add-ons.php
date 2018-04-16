<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Abstract Add-on Class
 *
 * add-on api
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_WShop_Add_Ons extends Abstract_WShop_Settings {
    /**
     * 插件版本
     * @var string
     * @since 1.0.0
     */
    public $version='1.0.0';
    
    /**
     * 作者
     * @var string
     * @since 1.0.0
     */
    public $author;
    
    /**
     * 插件介绍地址
     * @var string
     * @since 1.0.0
     */
    public $plugin_uri;
    
    /**
     * 作者地址
     * @var string
     * @since 1.0.0
     */
    public $author_uri;
    
    /**
     * 子插件设置地址
     * @var string
     * @since 1.0.0
     */
    public $setting_uris=array();
    
    /**
     * 激活的
     * @var bool
     */
    public $ia=false;
    
    /**
     * 第三方插件依赖
     * @var array
     * @since 1.0.0
     *  array(
     *      'id1'=>array(
     *          title1
     *      ), 
     *      'id2'=>array(
     *          title2
     *      )
     *  )
     */
    public $depends=array();
    
    /**
     * 要求核心插件最低版本
     * @var string
     * @since 1.0.0
     */
    public $min_core_version='1.0.0';
    
    /**
     * 插件是否已启用
     * @var bool
     * @since 1.0.0
     */
    public $is_active;
    
    /**
     * 插件启用时
     * @since 1.0.0
     */
    public function on_install(){}
    
    /**
     * 插件卸载时
     * @since 1.0.0
     */
    public function on_uninstall(){}
    /**
     * 插件加载时
     * @since 1.0.0
     */
	public function on_load(){}
	
	/**
	 * 插件
	 * do_action('init')
	 * @since 1.0.0
	 */
	public function on_init(){}	
	/**
	 * 插件
	 * do_action('wshop_after_init')
	 * @since 1.0.0
	 */
	public function on_after_init(){}
	
	/**
	 * 定时服务
	 */
	public function on_cron(){
	    
	}
	
	/**
	 * 注册短码
	 * @param array $codes
	 */
	public function add_shortcodes($shortcodes){ return $shortcodes;}
	
	/**
	 * 版本更新
	 * @param string $old_version 缓存版本号
	 * @since 1.0.0
	 */
	public function on_update($old_version){}
	
	/**
	 * 注册自定义文章类型
	 */
	public function register_post_types(){}
	
	/**
	 * 自定义编辑字段
	 */
	public function register_fields(){}
	
	/**
	 * @since 1.0.0
	 */
	public function do_ajax(){}
	
	/**
	 * 获取设置url
	 * @return string
	 * @since 1.1.7
	 */
	public function get_settings_url(){
	    if(isset($this->setting_uris['settings'])){
	        return $this->setting_uris['settings']['url'];
	    }
	     
	    return $this->setting_uri;
	}
}