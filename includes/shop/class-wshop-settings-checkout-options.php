<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class WShop_Settings_Checkout_Options extends Abstract_WShop_Settings{
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

    private function __construct(){
        $this->id='settings_default_checkout_options';
        $this->title=__('Checkout options',WSHOP);

        $this->init_form_fields();
    }

    public function process_admin_options(){
        parent::process_admin_options();
        
        do_action('wshop_flush_rewrite_rules');
        flush_rewrite_rules();
    }
    
    public function init_form_fields(){
        $form_fields = apply_filters('wshop_checkout_options_1', 
          array(
               'enable_guest_checkout'=>array(
                   'title'=>__('Enable guest checkout',WSHOP),
                   'type'=>'checkbox',
                   'default'=>'yes',
                   'description'=>__('Allows customers to checkout without creating an account.',WSHOP)
               ), 
               'order_expire_minute'=>array(
                   'title'=>__('Order Expire(minute)',WSHOP),
                   'type'=>'text',
                   'default'=>'30',
                   'description'=>__('When order expired,can not be paid again.(If set null or 0,order will be never expire)',WSHOP)
               ), 
               'order_prefix'=>array(
                   'title'=>__('Order prefix',WSHOP),
                   'type'=>'text',
                   'placeholder'=>'site1_'
               )
              
          ));
        
           $form_fields1 =apply_filters('wshop_checkout_options_2', array());
           
       
        $this->form_fields = array_merge($form_fields,$form_fields1);
    }
}
?>