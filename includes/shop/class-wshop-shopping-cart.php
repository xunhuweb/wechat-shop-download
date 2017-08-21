<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

class WShop_Shopping_Cart extends Abstract_WShop_Shopping_Cart{
    public function __construct($wp_cart=null){
        parent::__construct($wp_cart);
    }
}