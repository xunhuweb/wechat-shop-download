<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * @since 1.0.0
 * @author ranj
 */
class WShop_Menu_Default_Modal extends Abstract_WShop_Settings_Menu{
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
        $this->id='menu_default_modal';
        $this->title=__('Application',WSHOP);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WShop_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("wshop_admin_menu_{$this->id}", array());
    }
}


?>