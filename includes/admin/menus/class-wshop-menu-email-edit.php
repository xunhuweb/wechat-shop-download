<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
class WShop_Menu_Email_Edit extends Abstract_WShop_Settings_Menu{
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
        $this->id='add_ons_menu_email_edit';
        $this->title=__('Email',WSHOP);
    }

    /* (non-PHPdoc)
     * @see Abstract_WShop_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("wshop_admin_menu_{$this->id}", array(
            WShop_Menu_Email_Edit_Settings::instance()
        ));
    }
}  
class WShop_Menu_Email_Edit_Settings extends Abstract_WShop_Settings {
    /**
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }

    private function __construct(){
        $this->id='add_ons_modal_email_edit_settings';
        $this->title=__('Email',WSHOP);
    }

    public function admin_form_start(){}
     
    public function admin_options(){
        $id   = isset($_GET['id'])?$_GET['id']:null; 
     
        if (isset($_REQUEST['view'])&&$_REQUEST['view']=='edit' ) {
            require_once 'class-wshop-menu-email-edit-detail.php';
            $api = new WShop_Email_Edit_Detail($id);
    		$api->view();
        } else {
            require_once 'class-wshop-menu-email-edit-list.php';
            $api = new WShop_Email_Edit_List();
		    $api->view();
        }
	}
	
    public function admin_form_end(){} 
}


?>