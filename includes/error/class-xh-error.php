<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
/**
 * Error
 *
 * @since 1.0.0
 * @author 		ranj
 */
class WShop_Error{
    public $errcode,$errmsg,$data,$errors=array();

    /**
     * initialize
     * 
     * @since  1.0.0
     * @param int $errcode
     * @param string $errmsg
     */
	public function __construct($errcode=0, $errmsg='',$data=null) {
		$this->errcode = $errcode;
		$this->errmsg = $errmsg;
		$this->data = $data;
		$this->errors = array (
		    403 => __('Sorry!Your are offline.',WSHOP),
		    404 => __('The resource was not found!',WSHOP),
		    405 => __('Your account has been frozen!',WSHOP),
		    500 => __('Server internal error, please try again later!',WSHOP),
		    501 =>__('You are accessing unauthorized resources!',WSHOP),
		    600 =>__('Your request is invalid!',WSHOP),
		    700 => __('Frequent operation, please try again later!',WSHOP),
		    701 => __('Sorry,Your request is timeout!',WSHOP),
		    1000 => __('Sorry,Network error!',WSHOP)
		);
	}
	
	/**
	 * Success result.
	 * 
	 * @since  1.0.0
	 * @return WShop_Error
	 */
	public static function success($data=null) {
		return new WShop_Error ( 0, '' ,$data);
	}
	
	/**
	 * Unknow error result.
	 *
	 * @since  1.0.0
	 * @return WShop_Error
	 */
	public static function error_unknow() {
		return new WShop_Error ( - 1, __('Ops!Something is wrong.',WSHOP) );
	}
	
	public static function wp_error($error) {
	    if(is_wp_error($error))
	    return new WShop_Error ( - 1, $error->get_error_message() );
	    
	    return self::error_unknow();
	}
	/**
	 * Custom error result.
	 *
	 * @since  1.0.0
	 * @param string $errmsg
	 * @return WShop_Error
	 */
	public static function error_custom($errmsg='') {
	    if($errmsg instanceof Exception){
	        $errmsg ="errcode:{$errmsg->getCode()},errmsg:{$errmsg->getMessage()}";
	    }else if($errmsg instanceof WP_Error){
	        $errmsg ="errcode:{$errmsg->get_error_code()},errmsg:{$errmsg->get_error_message()}";
	    }
		return new WShop_Error ( - 1, $errmsg );
	}
	
	/**
	 * Defined error result.
	 *
	 * @since  1.0.0
	 * @param int $error_code
	 * @return WShop_Error
	 */
	public static function err_code($err_code) {
	    $self = WShop_Error::error_unknow ();
	    
	    if(isset($self->errors[$err_code])){
	        $self->errcode=$err_code;
	        $self->errmsg=$self->errors[$err_code];
	    }
	    
	    return $self;
	}
	
	/**
	 * check error result is valid.
	 *
	 * @since  1.0.0
	 * @param WShop_Error $wshop_error
	 * @return bool
	 */
	public static function is_valid(&$wshop_error) {
	    if(!$wshop_error){
	        $wshop_error = WShop_Error::error_unknow ();
	        return false;
	    }
	    
	    if($wshop_error instanceof WShop_Error){
	        return $wshop_error->errcode == 0;
	    }
	    

	    if(isset($wshop_error->errcode)){
	        return $wshop_error->errcode == 0;
	    }
	    
	    return true;
	}
	
	/**
	 * serialize the error result.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_json() {
		return json_encode ( array(
				'errcode'=>$this->errcode,
				'errmsg'=>$this->errmsg,
		         'data'=>$this->data
		));
	}
	
	public function to_wp_error(){
	    return new WP_Error($this->errcode,$this->errmsg,$this->data);
	}
	
	public function to_string(){
	    return "errcode:{$this->errcode};errmsg:{$this->errmsg}";
	}
}