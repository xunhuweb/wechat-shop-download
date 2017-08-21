<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
  
class WShop_Product extends Abstract_WShop_Product{
    /**
     * @param int|WP_Post|NULL $post
     * @since 1.0.0
     */
    public function __construct($post=null){
        parent::__construct($post);
        
    }
    
}


?>