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
class waImageImagick extends waImage
{
	/**
	 * @var  Imagick
	 */
	protected $im;

	/**
	 * Checks if ImageMagick is enabled.
	 */
	public static function check()
	{
		if (!extension_loaded('imagick'))
		{
			throw new waException(_ws('ImageMagick is not installed.'));
		}

		return self::$checked = true;
	}

	public function __construct($file)
	{
		if (!self::$checked) {
			self::check();
		}
		parent::__construct($file);

		$this->im = new Imagick;
		$this->im->readImage($file);
	}

	public function __destruct()
	{
		$this->im->clear();
		$this->im->destroy();
	}


	protected function _resize($width, $height)
	{
		if ($this->im->getNumberImages() > 1) {
			$this->im = $this->im->coalesceImages();
			foreach ($this->im as $animation) {
//				$animation->setImagePage( $animation->getImageWidth(), $animation->getImageHeight(), 0, 0 );
				$animation->resizeImage($width, $height, Imagick::FILTER_CUBIC, 0.5);
			}
		}
		else {
			if ($this->im->resizeImage($width, $height, Imagick::FILTER_CUBIC, 0.5))
			{
				$this->width = $this->im->getImageWidth();
				$this->height = $this->im->getImageHeight();
			}
		}
	}

	protected function _crop($width, $height, $offset_x, $offset_y)
	{
		if ($this->im->cropImage($width, $height, $offset_x, $offset_y))
		{
			$this->width = $this->im->getImageWidth();
			$this->height = $this->im->getImageHeight();

			return true;
		}

		return false;
	}

	protected function _rotate($degrees)
	{
		if ($this->im->rotateImage(new ImagickPixel, $degrees))
		{
			$this->width = $this->im->getImageWidth();
			$this->height = $this->im->getImageHeight();

			return true;
		}

		return false;
	}

	protected function _sharpen($amount)
	{
		//IM not support $amount under 5 (0.15)
		$amount = ($amount < 5) ? 5 : $amount;

		// Amount should be in the range of 0.0 to 3.0
		$amount = ($amount * 3.0) / 100;

		if ($this->im->sharpenImage(0, $amount))
		{
			$this->width = $this->im->getImageWidth();
			$this->height = $this->im->getImageHeight();

			return true;
		}

		return false;
	}

	protected function _save($file, $quality)
	{
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		$type = $this->_save_function($extension, $quality);
		$this->im->setImageCompressionQuality($quality);

		if ($this->im->getNumberImages() > 1 && $extension == "gif") {
			$res = $this->im->writeImages($file, true);
		}
		else {
			$res = $this->im->writeImage($file);
		}
		if ($res) {
			$this->type = $type;
			$this->mime = image_type_to_mime_type($type);
			return true;
		}
		return false;
	}

	protected function _render($type, $quality)
	{
		$type = $this->_save_function($type, $quality);
		$this->im->setImageCompressionQuality($quality);
		$this->type = $type;
		$this->mime = image_type_to_mime_type($type);

		if ($this->im->getNumberImages() > 1 && $type == "gif") {
			return $this->im->getImagesBlob();
		}
		return $this->im->getImageBlob();
	}

	protected function _save_function($extension, & $quality)
	{
		switch (strtolower($extension))
		{
			case 'jpg':
			case 'jpeg':
				$type = IMAGETYPE_JPEG;
				if ($this->type == "png" || $this->type == "gif") {
					$this->im->borderImage(new ImagickPixel("white"), 1, 1);
				}
				$this->im->setImageFormat('jpeg');
			break;
			case 'gif':
				$type = IMAGETYPE_GIF;
				$this->im->setImageFormat('gif');
			break;
			case 'png':
				$type = IMAGETYPE_PNG;
				$this->im->setImageFormat('png');
			break;
			default:
				throw new waException(_ws(sprintf('Installed ImageMagick does not support %s images', $extension)));
			break;
		}

		$quality = $quality - 5;

		return $type;
	}

	protected function _filter($type)
	{
	    switch ($type) {
	        case self::FILTER_GRAYSCALE:
	            $this->im->setImageColorSpace(Imagick::COLORSPACE_GRAY);
	            break;
	        default:
	            $this->im->setImageColorSpace(Imagick::COLORSPACE_GRAY);
	            break;
	    }
	}

	protected function _watermark($watermark, $opacity, $font_file = null)
	{
	    $opacity = min(max($opacity, 0), 100);
	    if ($watermark instanceof waImage) {

	        $offset = $this->calcWatermarkOffset($watermark->width, $watermark->height);
	        $watermark = new Imagick($watermark->file);
	        $watermark->setImageOpacity($opacity / 100);
	        $this->im->compositeImage(
	                $watermark,
	                Imagick::COMPOSITE_OVER,
	                $offset[0],
	                $offset[1]);
	    } else {
	        $text = (string) $watermark;
	        if (!$text) {
	            return;
	        }
	        $font_size = 24;    // 24px = 18pt
	        $font_color = new ImagickPixel('#000000');

	        $watermark = new ImagickDraw();
	        $watermark->setFillColor($font_color);
	        $watermark->setFillOpacity($opacity / 100);
	        if ($font_file && file_exists($font_file)) {
	            $watermark->setFont($font_file);
	        }
	        $watermark->setFontSize($font_size);

	        $metrics = $this->im->queryFontMetrics($watermark, $text);
	        $width = $metrics['textWidth'];
	        $height = $metrics['textHeight'];
	        $offset = $this->calcWatermarkOffset($width, $height);
	        $offset[1] = $offset[1] + $height;    // correcting cause is annotateImage's calc relatively bottom of text

	        $this->im->annotateImage($watermark, $offset[0], $offset[1], 0, $text);
	    }
	}

	private function calcWatermarkOffset($width, $height)
	{
	    return array(($this->width - $width) / 2, $height / 2);
	}

}
