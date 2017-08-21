<?php 

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 优惠码
 * @author rain
 *
 */
class WShop_Add_On_Ultimate_Member extends Abstract_WShop_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WShop_Add_On_Ultimate_Member
     */
    private static $_instance = null;

    /**
     * 插件跟路径url
     * @var string
     * @since 1.0.0
     */
    public $domain_url;
    public $domain_dir;
    
    /**
     * @since 1.0.0
     * @static
     * @return WShop_Add_On_Ultimate_Member
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct(){
        $this->id='wshop_add_ons_ultimate_member';
        $this->title=__('Ultimate_Member',WSHOP);
        $this->description ='在UM中创建我的订单，可以查询订单记录';
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',WSHOP);
        $this->author_uri='https://www.wpweixin.net';
        $this->domain_url = WShop_Helper_Uri::wp_url(__FILE__) ;
        $this->domain_dir = WShop_Helper_Uri::wp_dir(__FILE__) ;
    }
    
    public function on_install(){
        $um_options = get_option('um_options');
        if($um_options&&is_array($um_options)){
            $um_options['um_flush_stop']=1;
            update_option('um_options', $um_options);
        }
    }
    
    public function on_init(){
        add_filter('um_account_page_default_tabs_hook',array($this,'um_account_page_default_tabs_hook'),10,1);        
        add_action('um_account_tab__orders', array($this,'um_account_tab__orders'),10,1);
    }
    
    public function um_account_page_default_tabs_hook($tabs){
        $tabs[250]=array(
            'orders'=>array(
                'custom'=>true,
                'icon'=>'um-faicon-list-alt',
                'title'=> __('Orders',WSHOP)
            )
        );
      
	   return $tabs;
    }

    /**
     * 显示订单
     * @param array $info
     */
    public function um_account_tab__orders($info){
        global $ultimatemember;
        
        echo WShop_Hooks::wshop_account_my_orders(array(
            'location'=>$ultimatemember->account->tab_link('orders'),
            'pageSize'=>10
        ));
        
    }
}


return WShop_Add_On_Ultimate_Member::instance();
?>