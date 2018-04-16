<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class WShop_Settings_Default_Basic_Default extends Abstract_WShop_Settings{
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
        $this->id='settings_default_basic_default';
        $this->title=__('General',WSHOP);

        $this->init_form_fields();
    }

    public function init_form_fields(){
        $form_fields =array(
            'currency'=>array(
                'title'=>__('Currency',WSHOP),
                'type'=>'select',
                'func'=>true,
                'default'=>'CNY',
                'options'=>function(){
                    $currencies = WShop_Currency::get_currencies();
                    
                    $options = array();
                    foreach ($currencies as $currency=>$name){
                        $symbol = WShop_Currency::get_currency_symbol($currency);
                        $options[$currency] = "{$name}({$symbol})";
                    }
                    
                    return $options;
                }
            ),
            'exchange_rate'=>array(
                'title'=>__('Exchange Rate',WSHOP),
                'type'=>'text',
                'default'=>'1',
                'description'=>__('Set exchange rate to CNY. When currency is CNY, default 1.',WSHOP)
            ),
            'enable_async'=>array(
               'title'=>__('Enable async load',WSHOP),
               'label'=>__('Enabled/Disabled',WSHOP),
               'type'=>'checkbox',
               'description'=>__('Enable when your site\' page is cached(using cache plugins(wp super cache, etc.)).',WSHOP)
            ),
            'enable_mail'=>array(
                'title'=>__('Email enabled',WSHOP),
                'type'=>'checkbox',
                'default'=>'yes'
            ),
            'product_title'=>array(
                'title'=>__('Product settings',WSHOP),
                'type'=>'subtitle'
            ),
            'product_img_default'=>array(
                'title'=>__('Default product img',WSHOP),
                'type'=>'attachment'
            )
        );
        
        $this->form_fields = apply_filters('wshop_settings_basic', $form_fields);
    }
}
?>