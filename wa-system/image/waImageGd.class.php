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
        if (!is_resource($this->image)) {
            $this->image = $this->createGDImageResourse($this->file, $this->type);
        }
    }

    private function createGDImageResourse($file, $type)
    {
        switch ($type)
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
            throw new waException(sprintf(_ws('GD does not support %s images'), $type));
        }

        $image = $create_function($file);
        imagesavealpha($image, true);

        return $image;
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
                throw new waException(sprintf(_ws('GD does not support %s images'), $extension));
            break;
        }

        return array($save, $type);
    }

    protected function _filter($type, $params = array())
    {
        switch ($type) {
            case self::FILTER_GRAYSCALE:
                imagefilter($this->image, IMG_FILTER_GRAYSCALE);
                break;
            case self::FILTER_SEPIA:
                imagefilter($this->image, IMG_FILTER_GRAYSCALE);
                imagefilter($this->image, IMG_FILTER_COLORIZE, 0x70, 0x42, 0x14, 0x25);
                break;
            case self::FILTER_CONTRAST:
                $level = isset($params['level']) ? $params['level'] :
                        (isset($params[0]) ? $params[0] : 3);
                if ($level > 0) {
                    $level = min($level, 100);
                    $level = -$level;
                    imagefilter($this->image, IMG_FILTER_CONTRAST, $level);
                }
                break;
            case self::FILTER_BRIGHTNESS:
                $level = isset($params['level']) ? $params['level'] :
                        (isset($params[0]) ? $params[0] : 3);
                if ($level > 0) {
                    $level = min($level, 100);
                    imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
                }
                break;
            default:
                imagefilter($this->image, IMG_FILTER_GRAYSCALE);
                break;
        }
    }

    /**
     * @param array $options
     *     'watermark' => waImage|string $watermark
     *     'opacity' => float|int 0..1
     *     'align' => self::ALIGN_* const
     *     'font_file' => null|string If null - will be used some default font. Note: use when watermark option is text
     *     'font_size' => float Size of font. Note: use when watermark option is text
     *     'font_color' => string Hex-formatted of color (without #). Note: use when watermark option is text
     *     'text_orientation' => self::ORIENTATION_* const. Note: use when watermark option is text
     * @return mixed
     */
    protected function _watermark($options)
    {
        // export options to php-vars
        foreach ($options as $name => $value) {
            $$name = $value;
        }
        $opacity = min(max($opacity, 0), 1);
        imagealphablending($this->image, true);

        if ($watermark instanceof waImage) {

            $width = $watermark->width;
            $height = $watermark->height;
            $offset = $this->calcWatermarkOffset($width, $height, $align);
            $type = $watermark->type;
            $watermark = $this->createGDImageResourse($watermark->file, $type);
            imagecopymerge_alpha($this->image, $watermark, $offset[0], $offset[1], 0, 0, $width, $height, $opacity * 100);
            imagedestroy($watermark);

        } else {
            $text = (string) $watermark;
            if (!$text) {
                return;
            }

            $font_color = array(
                'r' => '0x'.substr($font_color, 0, 2),
                'g' => '0x'.substr($font_color, 2, 2),
                'b' => '0x'.substr($font_color, 4, 2),
                'a' => floor((1 - $opacity) * 127)
            );

            if ($font_file && file_exists($font_file)) {
                $metrics = imagettfbbox($font_size, 0, $font_file, $text);
                if ($metrics) {
                    $width = $metrics[2] - $metrics[0];
                    $height = $metrics[1] - $metrics[7];
                    if ($text_orientation == self::ORIENTATION_VERTICAL) {
                        list ($width, $height) = array($height, $width);
                    }
                    $offset = $this->calcWatermarkOffset($width, $height, $align);
                    $offset = $this->watermarkOffsetFix($offset, $width, $height, $align, $text_orientation);
                    $color = imagecolorallocatealpha($this->image, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                    imagettftext($this->image, $font_size, $text_orientation == self::ORIENTATION_VERTICAL ? 90 : 0, $offset[0], $offset[1], $color, $font_file, $text);
                } else {
                    throw new waException(_ws("Can't read font file $font_file"));
                }
            } else {
                $font = floor((5*$font_size)/12);
                if ($font < 1) {
                    $font = 1;
                } else if ($font > 5) {
                    $font = 5;
                }
                $width = imagefontwidth($font) * strlen($text);
                $height = imagefontheight($font);
                if ($text_orientation == self::ORIENTATION_VERTICAL) {
                    list ($width, $height) = array($height, $width);
                }
                $offset = $this->calcWatermarkOffset($width, $height, $align, $text_orientation);
                if ($text_orientation == self::ORIENTATION_VERTICAL) {
                    imagestring_rotate($this->image, $font, 90, $offset[0], $offset[1], $text, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                } else {
                    $color = imagecolorallocatealpha($this->image, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                    imagestring($this->image, $font, $offset[0], $offset[1], $text, $color);
                }
            }
        }
    }

    private function calcWatermarkOffset($width, $height, $align)
    {
        $offset = '';
        $margin = 10;
        switch ($align) {
            case self::ALIGN_TOP_LEFT:
                $offset = array($margin, $margin);
                break;
            case self::ALIGN_TOP_RIGHT:
                $offset = array($this->width - $width - $margin, $margin);
                break;
            case self::ALIGN_BOTTOM_LEFT:
                $offset = array($margin, $this->height - $height - $margin);
                break;
            case self::ALIGN_BOTTOM_RIGHT:
                $offset = array($this->width - $width - $margin, $this->height - $height - $margin);
                break;
        }
        return $offset;
    }

    private function watermarkOffsetFix($offset, $width, $height, $align, $orientation)
    {
        if ($orientation == self::ORIENTATION_HORIZONTAL) {
            $offset[1] += $height;
            if ($align == self::ALIGN_BOTTOM_LEFT || $align == self::ALIGN_BOTTOM_RIGHT) {
                $offset[1] -= $height/4;
            }
        } else {
            $offset[0] += $width;
            $offset[1] += $height;
            if ($align == self::ALIGN_BOTTOM_RIGHT) {
                $offset[0] -= $width/4;
            }
        }
        return $offset;
    }

    public function __destruct()
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
    }

}

function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
{
    // creating a cut resource
    $cut = imagecreatetruecolor($src_w, $src_h);

    $trans_color = imagecolorallocate($cut, 255, 255, 255);
    imagecolortransparent($cut, $trans_color);
    imagefill($cut, 0, 0, $trans_color);

    // copying relevant section from background to the cut resource
    imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
    // copying relevant section from watermark to the cut resource
    imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
    // insert cut resource to destination image
    imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);

    imagedestroy($cut);
}

function imagestring_rotate($dst_im, $font, $angle, $dst_x, $dst_y, $text, $r, $g, $b, $alpha)
{
    $width = imagefontwidth($font) * strlen($text);
    $height = imagefontheight($font);

    // create png with text that has transparent background
    $png = imagecreate($width, $height);
    $trans_color = imagecolorallocatealpha($png, 255, 255, 255, 127);

    // draw text and rotate
    $font_color = imagecolorallocatealpha($png, $r, $g, $b, $alpha);
    imagestring($png, $font, 0, 0, $text, $font_color);
    $png = imagerotate($png, $angle, $trans_color);
    imagealphablending($png, true);
    imagesavealpha($png, true);

    // copy images
    imagecopy($dst_im, $png, $dst_x, $dst_y, 0, 0, $height, $width);

    imagedestroy($png);
}