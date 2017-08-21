<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
 
abstract class Abstract_XH_WShop_Fields extends Abstract_WShop_Settings{   
    /**
     * @var array array(post_type,name)
     */
    protected $post_types ;
    
    protected function __construct(){
        add_action('admin_init', array($this,'admin_init'),10);
    }
    
    public function admin_init(){
        $this->post_types =$this->get_post_types();
        
        foreach ($this->post_types as $post_type=>$name){
            add_meta_box('wshop-metabox-'.$this->id,
                $this->title,
                array($this,'meta_box_html'),
                $post_type,
                'normal',
                'high' );
            
            add_action("save_post_{$post_type}", array($this,'save_meta_box_data'),10,3);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_WShop_Fields::meta_box_html()
     * @since 1.0.0
     */
    public function meta_box_html(){
        global $post;
         
        $this->_admin_options($post);
    }
    
    public function init_form_fields(){
        throw new Exception('invalid call');
    }
    
    abstract function _init_form_fields($post);
    
    public function process_object_update($post){
        global $wpdb;
        $product =$this->get_object($post);
        
        //如果post属性，则添加post_ID属性
        $this->sanitized_fields[$product->get_primary_key()]=$post->ID;
        
        $fields = array();
        foreach ($this->sanitized_fields as $key=>$val){
            $fields[$key] = maybe_serialize($val);
        }
       
        if(!$product->is_load()){
            foreach ($fields as $key=>$val){
                $product->{$key} = $val;
            }
            return $product->insert();
        }else{
            return $product->update($fields);
        }
    }
    /**
     *
     * {@inheritDoc}
     * @see Abstract_XH_WShop_Fields::sale_meta_box_data()
     * @since 1.0.0
     */
    public function save_meta_box_data($post_ID, $post, $update ){
        $this->_process_admin_options($post);
    }
    
    abstract function get_post_types();
  
    public function admin_options(){
        throw new Exception('invalid call');
    }
    
    public function _admin_options($post) {
        $this->_init_form_fields($post);
        $this->_init_settings($post);
        ?>
	    <style type="text/css">
            .form-table tr{display:block;}
        </style>
        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}
	
    public function get_option($key, $empty_value = null) {
	    if (empty ( $this->settings )) {
	        $this->settings=array();
	       // $this->init_settings ();
	    }
	
	    // Get option default if unset.
	    if (! isset ( $this->settings [$key] )) {
	        $form_fields = $this->get_form_fields ();
	        $this->settings [$key] = isset ( $form_fields [$key] ['default'] ) ? $form_fields [$key] ['default'] : '';
	    }
	
	    if (! is_null ( $empty_value ) && empty ( $this->settings [$key] ) && '' === $this->settings [$key]) {
	        $this->settings [$key] = $empty_value;
	    }
	
	    return $this->settings [$key];
	}
    
	public function init_settings() {
	    throw new Exception('invalid call');
	}
	
	public function _init_settings($post) {
	    $product = $this->get_object($post);
	    $this->settings=array();
	    if($product->is_load()){
	        foreach (get_object_vars($product) as $key=>$val){
	            if(method_exists($this, "set_{$key}_val")){
	               $this->settings[$key] = $this->{"set_{$key}_val"}($post,$product,$key,$val);
	            }else{
	                $this->settings[$key] = $val;
	            }
	        }
	    }
	    
	    if (! $this->settings || ! is_array ( $this->settings )) {
	        $this->settings = array ();
	         
	        // If there are no settings defined, load defaults.
	        $form_fields = $this->get_form_fields ();
	        if ($form_fields) {
	            foreach ( $form_fields as $k => $v ) {
	                $this->settings [$k] = isset ( $v ['default'] ) ? $v ['default'] : '';
	            }
	        }
	    }
	
	    if (! empty ( $this->settings ) && is_array ( $this->settings )) {
	        $this->settings = array_map ( array (
	            $this,
	            'format_settings'
	        ), $this->settings );
	        $this->enabled = isset ( $this->settings ['enabled'] ) && $this->settings ['enabled'] == 'yes' ? 'yes' : 'no';
	    }
	}
	
	public function process_admin_options() {
	    throw new Exception('invalid call');
	}
	
	public function display_errors() {
	    if(count( $this->errors)==0){
	        return;
	    }
	    
	    wp_die(__('Update failed,detail error:',WSHOP).join('<br/>',$this->errors));
	}
	
	public function _process_admin_options($post) {
	    global $post;
	  
	    if(!$post||in_array($post->post_status, array('auto-draft','trash'))){
	        return;
	    }
	    if(isset($_POST['action'])&&in_array($_POST['action'], array(
	        'trash',
	        'untrash'
	    ))){
	        return;
	    }
	    
	    
	    if(!apply_filters('wshop_do_post_process_admin_options', true,$post)){
	        return;
	    }
	    $this->_init_settings ($post);
	    $this->_init_form_fields($post);
	    $this->validate_settings_fields ();
	    if (count ( $this->errors ) > 0) {
	        $this->display_errors ();
	        return false;
	    } else {
	        try {
	            $error = $this-> process_object_update($post);
	            if($error instanceof WShop_Error&&!WShop_Error::is_valid($error)){
	                $this->errors[]=$error->to_json();
	                $this->display_errors ();
	                return false;
	            }
	            
	            foreach ($this->sanitized_fields as $key=>$val){
	                update_post_meta($post->ID, "wshop_{$key}", $val);
	            }
	        } catch (Exception $e) {
	            $this->errors[]=$e->getMessage();
	            $this->display_errors ();
	            return false;
	        }
	
	        $this->_init_settings ($post);
	        return true;
	    }
	}
	
	/**
	 * 
	 * @param WP_Post $post
	 * @return WShop_Post_Object
	 */
	abstract function get_object($post);
}
?>