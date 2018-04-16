<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class WShop_Admin {
    /**
     * Wp menu key
     *  
     * @var string
     * @since  1.0.0
     */
    const menu_tag='wshop';
    
    /**
     * 实例
     * 
     * @var WShop_Admin
     */
    private static $_instance;
    
    /**
     * WShop_Admin Instance
     * 
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * hook admin menu actions
     * @since  1.0.0
     */
    private function __construct(){      
        $this->includes();
        $this->hooks();
    }
 
    /**
     * include menu files
     * @since  1.0.0
     */
    public function includes(){
        require_once 'menus/class-wshop-page-default.php';
        require_once 'menus/class-wshop-page-add-ons.php';
        require_once 'menus/class-wshop-page-order.php';
        require_once 'menus/class-wshop-menu-default-basic.php';
        require_once 'menus/class-wshop-menu-default-modal.php';
        require_once 'menus/class-wshop-menu-default-payment-gateway.php';
        require_once 'menus/class-wshop-menu-add-ons-install.php';
        require_once 'menus/class-wshop-menu-add-ons-recommend.php';
        require_once 'menus/class-wshop-menu-email-edit.php';
        require_once 'menus/user/class-wshop-user-page-order.php';
    }
    
    /**
     * hooks
     * @since  1.0.0
     */
    public function hooks(){
        add_action( 'admin_menu', array( $this, 'admin_menu'),10);
        add_action( 'admin_head', array( $this, 'admin_head'),10 ); 
        
    }

	
    /**
     * Reset default wp menu display
     * 
     * @since  1.0.0
     */
    public function admin_head(){
        global $submenu;
    
        if(isset( $submenu[self::menu_tag] ) 
           &&isset($submenu[self::menu_tag][0])
           &&isset($submenu[self::menu_tag][0][2])
           &&$submenu[self::menu_tag][0][2]==self::menu_tag){
            
            unset( $submenu[self::menu_tag][0] );
        }
    }
    
    /**
     * 获取注册的菜单
     * @return []Abstract_WShop_Settings_Page
     * @since 1.0.0
     */
    public function get_admin_pages(){
        return apply_filters('wshop_admin_pages', array(
           10=> WShop_Page_Order::instance(),
           50=> WShop_Page_Default::instance(),
           100=> WShop_Page_Add_Ons::instance()
        ));
    }
  
    
    /**
     * @return NULL|Abstract_WShop_Settings_Page
     * @since 1.0.0
     */
    public function get_current_page(){
        global $pagenow;
        if($pagenow!='admin.php'){
            return null;
        }
    
        $page_id = isset($_GET['page'])?$_GET['page']:null;
        return WShop_Helper_Array::first_or_default($this->get_admin_pages(),function($m,$pid){
            return $m->get_page_id()==$pid;
        },$page_id);
    }
    
    /**
     * @return NULL|Abstract_WShop_Settings_Menu
     * @since 1.0.0
     */
    public function get_current_menu(){
        $current_page = $this->get_current_page();
        if(!$current_page){
            return null;
        }
       
        return $current_page->get_current_menu();
    }
    
    /**
     * 获取当前设置地址
     * @since 1.0.0
     * @return string 
     */
    public function get_current_admin_url($params = array()){
        $page = $this->get_current_page();
        $menu = $this->get_current_menu();
        $submenu = $this->get_current_submenu();
        
        $query="admin.php";
        
        if($page){
            $query.="?page={$page->get_page_id()}";
            if($menu){
                $query .="&section={$menu->id}";
            }
            
            if($submenu){
                $query .="&sub={$submenu->id}";
            }
        }
        
        if(count($params)>0){
            $query.="&".http_build_query($params);
        }
        return admin_url($query);
    }
    
    /**
     * 
     * @return NULL|Abstract_WShop_Settings
     * @since 1.0.0
     */
    public function get_current_submenu(){
        $current_menu = $this->get_current_menu();
        if(!$current_menu){
            return null;
        }
       
        return $current_menu->get_submenu();
    }
    
    /**
     * Wp menus
     * @since  1.0.0
     */
    public function admin_menu(){
        if(!is_user_logged_in()){
            return;
        }
        
        $menu_title = apply_filters('wshop_admin_menu_title', 'Wechat Shop');
             
       
        global $current_user;
        $pages = $this->get_admin_pages();
        ksort($pages);
        reset($pages);
        
        $capabilitys = apply_filters('wshop_manager_capabilitys', array('administrator'));
        
        $user_roles = $current_user->roles&&is_array($current_user->roles)?$current_user->roles:array();
        if(count($user_roles)==0){return;}
        
        $is_manager =false;
        foreach ($user_roles as $role){
            if(in_array($role, $capabilitys)){
                $is_manager=true;
                break;
            }
        }
       
        if(!$is_manager){return;}
        
        add_menu_page( $menu_title, $menu_title, 'read', self::menu_tag, null, null, '55.5' );
        foreach ($pages as $page){
            if(!$page||!$page instanceof Abstract_WShop_Settings_Page){
                continue;
            }
            
            add_submenu_page(
                self::menu_tag,
                $page->title,
                $page->title,
                'read',
                $page->get_page_id(),
                array($page,'render'));
        }     
    }
}