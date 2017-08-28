<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

// file: location of file 
// w: width
// h: height
// zc: zoom crop (0 or 1)
// q: quality (default is 75 and max is 100)
// either width or height can be used
class WShop_Timthumb {
	public $file_name,$file_dir;
	public function __construct($file_dir,$file_name){
		$this->file_name  = $file_name;
		$this->file_dir = $file_dir;
	}
	
	/**
	 *
	 * @param string $file
	 *        	图片文件
	 * @param int $new_width
	 *        	剪切宽度
	 * @param int $new_height
	 *        	剪切高度
	 * @param bool $zoom_crop        	
	 * @param int $quality
	 *        	质量
	 * @return WShop_Error
	 */
	public function make( $new_width, $new_height, $zoom_crop = false) {
		$file = $this->file_dir.$this->file_name;
		$owidth=$new_width;
		$oheight=$new_height;
		
		// get mime type of src
		$mime_type = $this->mime_type ( $file );
		$mime_type=strtolower ( $mime_type );
		// make sure that the src is gif/jpg/png
		if (! $this->valid_src_mime_type ( $mime_type )) {
			return WShop_Error::error_custom ( "Invalid src mime type: $mime_type" );
		}
		
		// check to see if GD function exist
		if (! function_exists ( 'imagecreatetruecolor' )) {
			return WShop_Error::error_custom ( "GD Library Error: imagecreatetruecolor does not exist" );
		}
		
		if (! file_exists ( $file )) {
			return WShop_Error::error_custom ( "$file not found!" );
		}
		
		// open the existing image
		$image = $this->open_image ( $mime_type, $file );
		if ($image === false) {
			return WShop_Error::error_custom ( 'Unable to open image : ' . $file );
		}
		
		$result = false;
		try {
		    // Get original width and height
		    $width = imagesx ( $image );
		    $height = imagesy ( $image );
		    
		    // generate new w/h if not provided
		    if ($new_width && ! $new_height) {
		        $new_height = $height * ($new_width / $width);
		    } elseif ($new_height && ! $new_width) {
		        $new_width = $width * ($new_height / $height);
		    } elseif (! $new_width && ! $new_height) {
		        $new_width = $width;
		        $new_height = $height;
		    }
		    $new_width = intval($new_width);
		    $new_height = intval($new_height);
		    
		    // create a new true color image
		    $canvas = imagecreatetruecolor ( $new_width, $new_height );
		    
		    try {
		        //透明
		        if($mime_type=='png'){
		            $alpha = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
		            imagefill($canvas, 0, 0, $alpha);
		        }
		    
		        if ($zoom_crop) {
		             
		            $filepath_x = $filepath_y = 0;
		            $filepath_w = $width;
		            $filepath_h = $height;
		             
		            $cmp_x = $width / $new_width;
		            $cmp_y = $height / $new_height;
		             
		            // calculate x or y coordinate and width or height of source
		             
		            if ($cmp_x > $cmp_y) {
		    
		                $filepath_w = round ( ($width / $cmp_x * $cmp_y) );
		                $filepath_x = round ( ($width - ($width / $cmp_x * $cmp_y)) / 2 );
		            } elseif ($cmp_y > $cmp_x) {
		    
		                $filepath_h = round ( ($height / $cmp_y * $cmp_x) );
		                $filepath_y = round ( ($height - ($height / $cmp_y * $cmp_x)) / 2 );
		            }
		             
		            if(!imagecopyresampled ( $canvas, $image, 0, 0, $filepath_x, $filepath_y, $new_width, $new_height, $filepath_w, $filepath_h )){
		                throw new Exception('Resize img failed!');
		            }
		        } else {
		             
		            // copy and resize part of an image with resampling
		            if(!imagecopyresampled ( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height )){
		                throw new Exception('Resize img failed!');
		            }
		        }
		    
		        $ext_index =strripos($this->file_name,'.');
		        if($ext_index===false){
		            return WShop_Error::error_custom ( 'Invalid file name: ' . $file );
		        }
		    
		        $new_file_name = substr($this->file_name, 0,$ext_index) . '-' . $owidth . 'x' . $oheight.'.' . substr($this->file_name, $ext_index+1);
		      
		        switch ($mime_type) {
		            case 'gif' :
		                $result =imagegif ( $canvas, $this->file_dir.$new_file_name );
		                break;
		            case 'jpg' :
		            case 'jpeg' :
		                $result =imagejpeg ( $canvas, $this->file_dir.$new_file_name, 100 );
		                break;
		            case 'png' :
		                if(imagesavealpha($canvas, true)){
		                    $result =imagepng ( $canvas, $this->file_dir.$new_file_name, 9);
		                }
		                break;
		            case 'bmp' :
		                $result =imagewbmp ( $canvas, $this->file_dir.$new_file_name );
		                break;
		            default :
		                break;
		        }
		    } catch (Exception $e) {
		        if($canvas){
		            imagedestroy ( $canvas );
		        }
		        return WShop_Error::err_code(500);
		    }
		} catch (Exception $e) {
		    if($image){
		      @imagedestroy($image);
		    }
		    
		    return WShop_Error::err_code(500);
		}
		
		@imagedestroy($image);
		@imagedestroy ( $canvas );
		if(!$result){
		    return WShop_Error::err_code(500);
		}
	
		return $new_file_name;
	}
	
	private function open_image($mime_type, $filepath) {
		$image = false;
		switch (strtolower ( $mime_type )) {
			case 'gif' :
				$image = imagecreatefromgif ( $filepath );
				break;
			case 'jpg' :
			case 'jpeg' :
				$image = imagecreatefromjpeg ( $filepath );
				break;
			case 'png' :
				$image = imagecreatefrompng ( $filepath );
				break;
			case 'bmp' :
				$image = imagecreatefromwbmp ( $filepath );
				break;
			default :
				break;
		}
		
		return $image;
	}
	
	private function mime_type($file) {
		$frags = explode ( ".", $file );
		$ext = strtolower ( $frags [count ( $frags ) - 1] );
		$types = array (
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp' 
	   );
		
		$mime_type = isset ( $types [$ext] ) ?$ext : '';
		
		if (! strlen ( $mime_type )) {
			$mime_type = 'unknown';
		}
		
		return ($mime_type);
	}
	
	private function valid_src_mime_type($mime_type) {
		if (preg_match ( "/jpg|jpeg|gif|png/i", $mime_type )) {
			return 1;
		}
		return 0;
	}
}

?>
