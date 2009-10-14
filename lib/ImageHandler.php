<?php

class ImageHandler {
	
	/**Class to handle processing of images for display on page
	 * 
	 */
	
	//define public variables
	public $img;
	public $img_name;
	public $img_type;
	public $output_type;
	public $new_img;
	public $keep_aspect = true;
	public $should_crop = false;
	
	//define private variables
	
	//define protected variables
	
	//define constructor
	function __construct($image_file) {
		if (!function_exists("imagecreate")) die("Error: GD Library is not available.");
		
		$this->img_name = $image_file;
		$this->img_type = $this->GetType();
		$this->Load();
		$this->output_type = $this->img_type;
	}
	
	function __destruct(){
		@imagedestroy($this->img);
    	@imagedestroy($this->new_img);
	}
	
	//define public functions
	public function Load(){
		switch($this->img_type){
			case "jpeg" : 
				$this->img = &imagecreatefromjpeg($this->img_name);
				break;
			case "gif" :
				$this->img = &imagecreatefromgif($this->img_name);
				break;
			case "png" :
				$this->img = &imagecreatefrompng($this->img_name);
				break;
			default: 
				die("Could Not Load Image - Invalid File Type - " . $this->img_name);
				break;
		}
	}
	
	public function Resize($dimensions,$img = NULL){
		
		$orig_dimensions = $this->GetDimensions();
		if(is_null($img)){
			$img = $this->img;
		} else {
			$orig_dimensions[0] = imagesx($img);
			$orig_dimensions[1] = imagesy($img);
		}
		
		
		$new_dimensions = array();
		$crop = false;
				
		//if new dimensions are larger than original exit function
		if($dimensions[0] > $orig_dimensions[0] && $dimensions[1] > $orig_dimensions[1]){
			$this->new_img = $img;
		} else {
		
			if($this->keep_aspect == true){
			
				if($dimensions[0]>$dimensions[1] || ($dimensions[1]==$dimensions[0] && $orig_dimensions[1] > $orig_dimensions[0])){
					array_push($new_dimensions,$dimensions[0]);
					$double_height = ($dimensions[0] / $orig_dimensions[0]) * $orig_dimensions[1] ;
					$height = intval(round($double_height));
					array_push($new_dimensions,$height);
				} else if($dimensions[1]>$dimensions[0] || ($dimensions[1]==$dimensions[0] && $orig_dimensions[0] > $orig_dimensions[1])){
					$double_width = ($dimensions[1] / $orig_dimensions[1]) * $orig_dimensions[0] ;
					$width = intval(round($double_width));
					array_push($new_dimensions,$width);
					array_push($new_dimensions,$dimensions[1]);
				} else {
					$new_dimensions = $dimensions;
				}
				
				if($new_dimensions !== $dimensions && $this->should_crop == true){
					$crop = true;
				}	
			
			} else {

				$new_dimensions = $dimensions;
			
			}

			
		
			$this->new_img = imagecreatetruecolor($new_dimensions[0], $new_dimensions[1]);
		
			if(($orig_dimensions[2] == 1) OR ($orig_dimensions[2]==3)){
  
        		imagealphablending($this->new_img, false);
  		        imagesavealpha($this->new_img,true);
  			    $transparent = imagecolorallocatealpha($this->new_img, 255, 255, 255, 127);
  		        imagefilledrectangle($this->new_img, 0, 0, $new_dimensions[0], $new_dimensions[0], $transparent);
  
  		     }
		
			//copy data from original to new graphic, resampling and resizing image
	    	imagecopyresampled($this->new_img,$img,0,0,0,0,$new_dimensions[0],$new_dimensions[1],$orig_dimensions[0],$orig_dimensions[1]);
	    	
	    	if($crop == true){
	    		$crop_x = ($new_dimensions[0]/2) - ($dimensions[0]/2);
				$crop_y = ($new_dimensions[1]/2) - ($dimensions[1]/2);
	    		$this->Crop(array($crop_x,$crop_y),$dimensions,$this->new_img);
	    	}
				    	
		}
	}
	
	public function Crop($coords,$dimensions,$img = NULL){
		
		if(is_null($img)){
			$img = $this->img;
		}
		
		$this->new_img = imagecreatetruecolor($dimensions[0],$dimensions[1]);
		imagecopyresampled($this->new_img,$img,0,0,$coords[0],$coords[1],$dimensions[0],$dimensions[1],$dimensions[0],$dimensions[1]);

	}
	
	public function Mirror($start_alpha=50,$gap=0,$reflection_portion=2.5,$direction = 'vertical',$img = NULL){
		
		if(is_null($img)){
			$img = $this->img;
		}
		
		$orig_dimensions = $this->GetDimensions();
		$new_dimensions = array();
		if($direction == 'vertical'){
			array_push($new_dimensions,$orig_dimensions[0]);
			array_push($new_dimensions, $orig_dimensions[1] + ($orig_dimensions[1] / $reflection_portion) + $gap);
		} else {
			array_push($new_dimensions, $orig_dimensions[0] + ($orig_dimensions[0] / $reflection_portion) + $gap);
			array_push($new_dimensions,$orig_dimensions[1]);
		}
 
 		$this->new_img = imagecreatetruecolor($new_dimensions[0], $new_dimensions[1]);
  		imagealphablending($this->new_img, false);
  		imagesavealpha($this->new_img, true);
		 
  		imagecopy($this->new_img, $this->img,0, 0, 0, 0, $orig_dimensions[0], $orig_dimensions[1]);
		  
  		if($direction == 'vertical'){
			$reflection_height =($orig_dimensions[1] / $reflection_portion);
			$alpha_step = $start_alpha / $reflection_height;
		 
			for($y=1;$y <= $gap; $y++){
  				for ($x = 0; $x < $new_dimensions[0]; $x++) {
  					$rgba = imagecolorat($this->img, 0, 0);
  					$rgba = imagecolorsforindex($this->img, $rgba);
  					$rgba = imagecolorallocatealpha($this->new_img, $rgba['red'], $rgba['green'], $rgba['blue'], 127);
  					imagesetpixel($this->new_img, $x, $orig_dimensions[1] + $y - 1, $rgba);
				}
  			}
		
  			for ($y = ($gap + 1); $y <= ($reflection_height+$gap); $y++) {
	    		for ($x = 0; $x < $orig_dimensions[0]; $x++) {
					//copy pixel from x / $src_height - y to x / $src_height + y
      				$rgba = imagecolorat($this->img, $x, $orig_dimensions[1] - $y+$gap);
      				$alpha = ($rgba & 0x7F000000) >> 24;
      				$alpha =  max($alpha, 27 + (($y-$gap) * $alpha_step));
      				$rgba = imagecolorsforindex($this->img, $rgba);
      				$rgba = imagecolorallocatealpha($this->new_img, $rgba['red'], $rgba['green'], $rgba['blue'], $alpha);
      				imagesetpixel($this->new_img, $x, $orig_dimensions[1] + $y - 1, $rgba);
    			}
  			}
  		} else {
		  	
  			$reflection_width =($orig_dimensions[0] / $reflection_portion);
			$alpha_step = $start_alpha / $reflection_width;
		 
			for($x=1;$x <= $gap; $x++){
  				for ($y = 0; $y < $new_dimensions[1]; $y++) {
  					$rgba = imagecolorat($this->img, 0, 0);
  					$rgba = imagecolorsforindex($this->img, $rgba);
  					$rgba = imagecolorallocatealpha($this->new_img, $rgba['red'], $rgba['green'], $rgba['blue'], 127);
  					imagesetpixel($this->new_img, $orig_dimensions[0] + $x-1, $y, $rgba);
				}
  			}
		
  			for ($x = ($gap + 1); $x <= ($reflection_width+$gap); $x++) {
	    		for ($y = 0; $y < $orig_dimensions[1]; $y++) {
			//		copy pixel from x / $src_height - y to x / $src_height + y
      				$rgba = imagecolorat($this->img, $orig_dimensions[0] - $x+$gap, $y);
      				$alpha = ($rgba & 0x7F000000) >> 24;
      				$alpha =  max($alpha, 27 + (($x-$gap) * $alpha_step));
      				$rgba = imagecolorsforindex($this->img, $rgba);
      				$rgba = imagecolorallocatealpha($this->new_img, $rgba['red'], $rgba['green'], $rgba['blue'], $alpha);
      				imagesetpixel($this->new_img, $orig_dimensions[0] + $x - 1, $y, $rgba);
    			}
  			}
		  	
  		}
	}
	
	public function Fit($dimensions,$img=NULL){
		
		$orig_dimensions = $this->GetDimensions();
		$new_dimensions = array();
		
		if($dimensions[0] > $orig_dimensions[0] && $dimensions[1] > $orig_dimensions[1]){
		$this->new_img = $img;
		} else {
		if($orig_dimensions[0] > $orig_dimensions[1] || ($dimensions[1]==$dimensions[0] && $orig_dimensions[1] > $orig_dimensions[0])){
			
			array_push($new_dimensions,$dimensions[0]);
			$double_height = ($dimensions[0] / $orig_dimensions[0]) * $orig_dimensions[1] ;
			$height = intval(round($double_height));
			array_push($new_dimensions,$height);
			
		}else if($orig_dimensions[1]>$orig_dimensions[0] || ($dimensions[1]==$dimensions[0] && $orig_dimensions[0] > $orig_dimensions[1])){
					$double_width = ($dimensions[1] / $orig_dimensions[1]) * $orig_dimensions[0] ;
					$width = intval(round($double_width));
					array_push($new_dimensions,$width);
					array_push($new_dimensions,$dimensions[1]);
		}
		$this->Resize($new_dimensions);
		}
	}
	public function Round($radius=12,$corners=array(true),$bg=NULL,$img=NULL){
	public function Overlay($overlay,$position='middle',$ratio=0.5,$img=NULL){
		
		$orig_dimensions = $this->GetDimensions();
		$overlay_dimensions = $overlay->GetDimensions();

		$overlay_width = $orig_dimensions[0] * $ratio;
		$overlay_height = $overlay_width/$overlay_dimensions[0] * $overlay_dimensions[1];
		$overlay->Resize(array($overlay_width,$overlay_height));
		
		//calculate position of overlay
		if(!is_array($position)){
			switch($position){
				case 'top' : 	$overlay_x = ($orig_dimensions[0]/2) - ($overlay_width/2);
								$overlay_y = 0;
							 	break;
				case 'middle' : $overlay_x = ($orig_dimensions[0]/2) - ($overlay_width/2);
								$overlay_y = ($orig_dimensions[1]/2) - ($overlay_height/2);
								break;
				case 'bottom' : $overlay_x = ($orig_dimensions[0]/2) - ($overlay_width/2);
								$overlay_y = $orig_dimensions[1] - $overlay_height;
								break;
				case 'topleft': $overlay_x = 0;
								$overlay_y = 0;
								break;
				case 'left' :   $overlay_x = 0;
								$overlay_y = ($orig_dimensions[1]/2) - ($overlay_height/2);
								break;
				case 'bottomleft' : $overlay_x = 0;
									$overlay_y = $orig_dimensions[1] - $overlay_height;
									break;
				case 'topright' : $overlay_x = $orig_dimensions[0] - $overlay_width;
								  $overlay_y = 0;
								  break;
				case 'right' : $overlay_x = $orig_dimensions[0] - $overlay_width;
								$overlay_y = ($orig_dimensions[1]/2) - ($overlay_height/2);
								break;
				case 'bottomright': $overlay_x = $orig_dimensions[0] - $overlay_width;
									$overlay_y = $orig_dimensions[1] - $overlay_height;
									break;
				default: 		$overlay_x = ($orig_dimensions[0]/2) - ($overlay_width/2);
								$overlay_y = ($orig_dimensions[1]/2) - ($overlay_height/2);
								break;
				
				
			}
		} else {
			
			$overlay_x = $position[0];
			$overlay_y = $position[1];
			
		}
		
		imagecopy($img, $overlay->new_img, $overlay_x, $overlay_y, 0, 0, $overlay_width, $overlay_height);

		$this->new_img = $img;
		
	}
	public function TextOverlay($text,$font,$position="",$size='12pt',$angle=0,$img=NULL){
	public function Display(){
		if(is_null($this->new_img)){
			$this->new_img = $this->img;
		}
		$this->Output($this->output_type);
	}
	
	public function Save($location){
		$this->Output($this->output_type,$location);
	}
	
	public function GetType(){
		$image_info = getimagesize($this->img_name);
		return image_type_to_extension($image_info[2],false);
	}
	
	public function GetDimensions(){
		return getimagesize($this->img_name);
	}
	
	public function setOutputType($new_type){
		$this->output_type = $new_type;
	}
	
	public static function IsImage($name){
		return getimagesize($name);		
	}
	//define private functions
	private function Output($type,$location="",$quality=100){

		//clean output buffer
		//while (@ob_end_clean());
		
		//output file based on image type
		switch ($type){
			case "jpeg" : 
				if($location=="")
					header("Content-Type: image/jpeg");
				imagejpeg($this->new_img,$location,$quality);
				break;
			case "gif" :
			if($location=="")
					header("Content-Type: image/gif");
				imagegif($this->new_img,$location,$quality);
				break;
			case "png" :
				if($location=="")
					header("Content-Type: image/png");
				imagepng($this->new_img,$location,($quality/100)*9,NULL);
				break;
			default :
				if($location=="")			
					header("Content-Type: image/jpeg");
				imagejpeg($this->new_img,$location,$quality);
				break;
		}
	}

	
}

?>