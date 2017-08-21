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
        
           $form_fields1 =apply_filters('wshop_checkout_options_2', 
              array(
               'title_1'=>array(
                    'title'=>__('Checkout pages',WSHOP),
                    'type'=>'subtitle',
                   'description'=>__('Checkout pages contains shipping address or other custom fields.',WSHOP)
               ),
               'page_checkout'=>array(
                   'title'=>__('Checkout page',WSHOP),
                   'type'=>'select',
                   'func'=>true,
                   'options'=>array($this,'get_page_options')
               )));
               
           $form_fields2 =  apply_filters('wshop_checkout_options_3', 
              array(
                'title2'=>array(
                    'title'=>__('Checkout endpoints',WSHOP),
                    'type'=>'subtitle',
                   'description'=>__('Endpoints are appended to your page URLs to handle specific actions during the checkout process. They should be unique.',WSHOP)
               ),
               'endpoint_order_pay'=> array(
                   'title'=>__('Pay',WSHOP),
                   'type'=>'text',
                   'default'=>'order-pay',
                   'description'=>__('Endpoint for the "Checkout &rarr; Pay" page.',WSHOP)
               ),
               'endpoint_order_received'=> array(
                   'title'=>__('Order received',WSHOP),
                   'type'=>'text',
                   'default'=>'order-received',
                   'description'=>__('Endpoint for the "Checkout &rarr; Order received" page.',WSHOP)
               )
             )
        );
        
        $this->form_fields = array_merge($form_fields,$form_fields1,$form_fields2);
    }
}
?>