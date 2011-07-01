<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage image
 */
class waImageGd extends waImage 
{
	protected $image;
	
	public function __construct($file)
	{
		if (!self::$checked) {
			self::check();
		}
		parent::__construct($file);

		switch ($this->type)
		{
			case IMAGETYPE_JPEG: {
				$create_function = 'imagecreatefromjpeg';
				break;
			}
			case IMAGETYPE_GIF: {
				$create_function = 'imagecreatefromgif';
				break;
			}
			case IMAGETYPE_PNG: {
				$create_function = 'imagecreatefrompng';
				break;
			}
		}

		if ( !isset($create_function) || !function_exists($create_function))
		{
			throw new waException(sprintf(_ws('GD does not support %s images'), $this->type));
		}
		
		if (!is_resource($this->image)) {
			$this->image = $create_function($this->file);
			imagesavealpha($this->image, true);
		}
	}
	
	public static function check()
	{
		if (!function_exists('gd_info')) {
			throw new waException(_ws('GD is not installed'));
		}
		$info = gd_info();
		preg_match('/\d+\.\d+(?:\.\d+)?/', $info['GD Version'], $matches);
		$version = $matches[0];

		if (!version_compare($version, '2.0', '>=')) {
			throw new waException(_ws('Need GD version of the above 2.0.1'));
		}
		return self::$checked = true;
	}

	protected function _create($width, $height)
	{
		// Create an empty image
		$image = imagecreatetruecolor($width, $height);
		// Do not apply alpha blending
		imagealphablending($image, false);
		// Save alpha levels
		imagesavealpha($image, true);
		return $image;
	}

	protected function _resize($width, $height)
	{
		$pre_width = $this->width;
		$pre_height = $this->height;

		$image = $this->_create($width, $height);

		if ( function_exists('imagecopyresampled') ) {
			if (imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $pre_width, $pre_height))
			{
				$this->updateInfo($image);
			}
		}
		else {
			if (imagecopyresized($image, $this->image, 0, 0, 0, 0, $width, $height, $pre_width, $pre_height))
			{
				$this->updateInfo($image);
			}
		}
	}
	
	protected function _rotate($degrees)
	{
		$transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
		if (function_exists("imagerotate")) {
			$image = imagerotate($this->image, 360 - $degrees, $transparent, 1);
		}
		else {
			
		}
		imagesavealpha($image, true);
		$this->updateInfo($image);
	}

	protected function updateInfo($image)
	{
		imagedestroy($this->image);
		$this->image = $image;
		
		$this->width  = imagesx($image);
		$this->height = imagesy($image);
	}
	
	protected function _crop($width, $height, $offset_x, $offset_y)
	{
		$image = $this->_create($width, $height);

		if ( function_exists('imagecopyresampled') ) {
			if (imagecopyresampled($image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height))
			{
				$this->updateInfo($image);
			}
		}
		else {
			if (imagecopyresized($image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height))
			{
				$this->updateInfo($image);
			}
		}
	}


	protected function _sharpen($amount)
	{
		// Amount should be in the range of 18-10
		$amount = round(abs(-18 + ($amount * 0.08)), 2);

		if (function_exists("imageconvolution")) {
			// Gaussian blur matrix
			$matrix = array
			(
				array(-1,   -1,    -1),
				array(-1, $amount, -1),
				array(-1,   -1,    -1),
			);
			if (imageconvolution($this->image, $matrix, $amount - 8, 0))
			{
				$this->width  = imagesx($this->image);
				$this->height = imagesy($this->image);
			}
		}
		else {
			$img = $this->image;
			
			$radius = 1;
			$threshold = 1;
			
			if ($amount > 500)    $amount = 500; 
                $amount = $amount * 0.016; 
                if ($radius > 50)    $radius = 50; 
                $radius = $radius * 2; 
                if ($threshold > 255)    $threshold = 255; 
                 
                $radius = abs(round($radius));     // Only integers make sense. 
			
			
			$w = imagesx($img); 
			$h = imagesy($img); 
			$imgCanvas = imagecreatetruecolor($w, $h); 
			$imgBlur = imagecreatetruecolor($w, $h); 
			// Move copies of the image around one pixel at the time and merge them with weight 
            // according to the matrix. The same matrix is simply repeated for higher radii. 
			for ($i = 0; $i < $radius; $i++)    { 
				imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left 
				imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right 
				imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center 
				imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h); 

				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up 
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down 
			} 
			
			imagedestroy($imgCanvas); 
			imagedestroy($imgBlur); 
                 
			$this->image = $img;
		}
	}

	protected function _save($file, $quality)
	{
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		list($save, $type) = $this->_save_function($extension, $quality);

		// Check if this image is PNG or GIF, then set if Transparent
		if ($extension == "jpg" && ($this->type == IMAGETYPE_PNG || $this->type == IMAGETYPE_GIF)) 
		{
			$output = imagecreatetruecolor($this->width, $this->height);
			$white = imagecolorallocate($output,  255, 255, 255);
			imagefilledrectangle($output, 0, 0, $this->width, $this->height, $white);
			imagecopy($output, $this->image, 0, 0, 0, 0, $this->width, $this->height);
			$this->image = $output;
		}
    	    
		$status = isset($quality) ? $save($this->image, $file, $quality) : $save($this->image, $file);

		if ($status === true && $type !== $this->type)
		{
			$this->type = $type;
			$this->mime = image_type_to_mime_type($type);
		}

		return true;
	}

	protected function _save_function($extension, & $quality)
	{
		switch (strtolower($extension))
		{
			case 'jpg':
			case 'jpeg':
				$save = 'imagejpeg';
				$type = IMAGETYPE_JPEG;
			break;
			case 'png':
				$save = 'imagepng';
				$type = IMAGETYPE_PNG;
				//does not affect quality
				$quality = 9;
			break;
			case 'gif':
				$save = 'imagegif';
				$type = IMAGETYPE_GIF;
				$quality = NULL;
			break;
			default:
				throw new waException(sprintf(_ws('GD not support %s images'), $extension));
			break;
		}

		return array($save, $type);
	}
	
	public function __destruct()
	{
		if (is_resource($this->image))	{
			imagedestroy($this->image);
		}
	}

}
