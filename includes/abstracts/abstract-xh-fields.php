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
         
        $this->admin_options();
    }
    
    public function process_object_update($post){
        global $wpdb;
        $product =$this->get_object($post);
        if(!$product){
            return WShop_Error::success();
        }
        //如果post属性，则添加post_ID属性
        $this->sanitized_fields[$product->get_primary_key()]=$post->ID;
       
        if(!$product->is_load()){
            foreach ($this->sanitized_fields as $key=>$val){
                $product->{$key} = $val;
            }
            
            $error =  $product->insert();
        }else{
            $error =  $product->update($this->sanitized_fields);
        }
        
        $error = apply_filters('wshop_process_object_update', $error,$product);
        return apply_filters("wshop_{$post->post_type}_process_object_update", $error,$product);
    }
    /**
     *
     * {@inheritDoc}
     * @see Abstract_XH_WShop_Fields::sale_meta_box_data()
     * @since 1.0.0
     */
    public function save_meta_box_data($post_ID, $post, $update ){
        $this->process_admin_options($post);
    }
    
    abstract function get_post_types();
    public function is_admin_client(){return is_admin();}
    public function admin_options(){
        $this->init_form_fields();
        $this->init_settings();
        ?>
	    <style type="text/css">
            .form-table tr{display:block;}
        </style>
        <input type="hidden" name="wshop_post_fields" value="<?php echo $this->id?>"/>
        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
    }

	public function init_form_fields(){
	    //兼容老版本插件 "_init_form_fields"
	    if(method_exists($this, '_init_form_fields')){
	        if(func_num_args()==0){
	            global $post;
	        }else{
	            global $post;
	            $post = func_get_arg(0);
	        }
	        $this->_init_form_fields($post);
	    }else{
	        parent::init_form_fields($post);
	    }
	}
	
	public function init_settings() {
	    if(func_num_args()==0){
	        global $post;
	    }else{
	        global $post;
	        $post = func_get_arg(0);
	    }
	    
	    $product = $this->get_object($post);
	    if(!$product){return;}
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
	    if(func_num_args()==0){
	        global $post;
	    }else{
	        global $post;
	        $post = func_get_arg(0);
	    }
	   
	    $wp_error=func_num_args()>1?func_get_arg(1):false;
	   
	    if(!$post
	        ||!isset($_POST['wshop_post_fields'])
	        ||
	        (isset($_REQUEST['post_ID'])&&$post->ID!=$_REQUEST['post_ID'])
	        ||
	        (isset($_REQUEST['post_type'])&&$post->post_type!=$_REQUEST['post_type'])
	        ){
	        return $wp_error?WShop_Error::error_unknow():false;
	    }
	   
	    $this->init_settings ($post);
	    $this->init_form_fields($post);
	    $this->validate_settings_fields ();
	    if (count ( $this->errors ) > 0) {
	        if($wp_error){
	            return WShop_Error::error_custom($this->errors[0]);
	        }
	        $this->display_errors ();
	        return false;
	    } 
	    
        try {
            $error = $this-> process_object_update($post);
            if(!WShop_Error::is_valid($error)){
                if($wp_error){
                    return $error;
                }
                
                $this->errors[]=$error->to_json();
                $this->display_errors ();
                return false;
            }
            
            foreach ($this->sanitized_fields as $key=>$val){
                update_post_meta($post->ID, "wshop_{$key}", $val);
            }
        } catch (Exception $e) {
            if($wp_error){
                return WShop_Error::error_custom($e->getMessage());
            }
            
            $this->errors[]=$e->getMessage();
            $this->display_errors ();
            return false;
        }

        $this->init_settings ($post);
        
        return $wp_error?WShop_Error::success():true;
	}
	
	public function display_errors() {
	    if(count( $this->errors)==0){
	        return;
	    }
	    
	    wp_die(__('Update failed,detail error:',WSHOP).join('<br/>',$this->errors));
	}
	
	/**
	 * 
	 * @param WP_Post $post
	 * @return WShop_Post_Object
	 */
	abstract function get_object($post);
}
?>