<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 菜单：登录设置
 *
 * @since 1.0.0
 * @author ranj
 */
class WShop_Page_Default extends Abstract_WShop_Settings_Page{    
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 菜单初始化
     *
     * @since  1.0.0
     */
    private function __construct(){
        $this->id='page_default';
        $this->title=__('Settings',WSHOP);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WShop_Settings_Menu::menus()
     */
    public function menus(){
        $submenus =array(
             WShop_Menu_Default_Basic::instance(),
            WShop_Menu_Default_Modal::instance(),
            WShop_Menu_Default_Payment_Gateway::instance(),
            WShop_Menu_Email_Edit::instance()
        );
        
        return apply_filters("wshop_admin_page_{$this->id}", WShop_Helper_Array::where($submenus, function($m){
            $menus= $m->menus();
            return count($menus)>0;
        }));
    }
}?>