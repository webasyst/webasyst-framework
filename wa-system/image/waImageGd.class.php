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
        self::check();
        parent::__construct($file);
        if (!is_resource($this->image)) {
            $this->image = $this->createGDImageResourse($this->file, $this->type);
        }
    }

    private function createGDImageResourse($file, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                $create_function = 'imagecreatefromjpeg';
                break;
            case IMAGETYPE_GIF:
                $create_function = 'imagecreatefromgif';
                break;
            case IMAGETYPE_PNG:
                $create_function = 'imagecreatefrompng';
                break;
            case IMAGETYPE_WEBP:
                $create_function = 'imagecreatefromwebp';
                break;
        }

        if (!isset($create_function) || !function_exists($create_function)) {
            throw new waException(sprintf(_ws('GD does not support %s images'), $type));
        }

        $image = $create_function($file);
        imagesavealpha($image, true);

        return $image;
    }

    public static function check()
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        if (!function_exists('gd_info')) {
            throw new waException(_ws('GD is not installed'));
        }
        $info = gd_info();
        preg_match('/\d+\.\d+(?:\.\d+)?/', $info['GD Version'], $matches);
        $version = $matches[0];

        if (!version_compare($version, '2.0', '>=')) {
            throw new waException(_ws('Need GD version of the above 2.0.1'));
        }

        /** для PHP ниже 7.1.0 */
        if (!defined('IMAGETYPE_WEBP')) {
            define('IMAGETYPE_WEBP', 18);
        }

        return $checked = true;
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

        if (function_exists('imagecopyresampled')) {
            if (imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $pre_width, $pre_height)) {
                $this->updateInfo($image);
            }
        } elseif (imagecopyresized($image, $this->image, 0, 0, 0, 0, $width, $height, $pre_width, $pre_height)) {
            $this->updateInfo($image);
        }
    }

    protected function _rotate($degrees)
    {

        if (function_exists("imagerotate")) {
            $transparent = ($degrees % 90 != 0) ? imagecolorallocatealpha($this->image, 0, 0, 0, 127) : null;
            $image = imagerotate($this->image, 360 - $degrees, $transparent, 1);
        } else {

        }
        imagesavealpha($image, true);
        $this->updateInfo($image);
    }

    protected function updateInfo($image = null)
    {
        if($image) {
            imagedestroy($this->image);
            $this->image = $image;
        }
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    protected function _crop($width, $height, $offset_x, $offset_y)
    {
        $image = $this->_create($width, $height);

        if (function_exists('imagecopyresampled')) {
            if (imagecopyresampled($image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height)) {
                $this->updateInfo($image);
            }
        } else if (imagecopyresized($image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height)) {
            $this->updateInfo($image);
        }
    }


    protected function _sharpen($amount)
    {
        // Amount should be in the range of 18-10
        $amount = round(abs(-18 + ($amount * 0.08)), 2);

        if (function_exists("imageconvolution")) {
            // Gaussian blur matrix
            $matrix = array(
                array(-1, -1, -1),
                array(-1, $amount, -1),
                array(-1, -1, -1),
            );
            if (imageconvolution($this->image, $matrix, $amount - 8, 0)) {
                $this->updateInfo();
            }
        } else {
            $radius = 1;
            $amount = min(500,$amount)*0.016;
            $radius = abs(round(min(50,$radius) * 2)); // Only integers make sense.

            $imgCanvas = imagecreatetruecolor($this->width, $this->height);
            $imgBlur = imagecreatetruecolor($this->width, $this->height);
            // Move copies of the image around one pixel at the time and merge them with weight
            // according to the matrix. The same matrix is simply repeated for higher radii.
            for ($i = 0; $i < $radius; $i++) {
                imagecopy($imgBlur, $this->image, 0, 0, 1, 0, $this->width - 1, $this->height); // left
                imagecopymerge($imgBlur, $this->image, 1, 0, 0, 0, $this->width, $this->height, 50); // right
                imagecopymerge($imgBlur, $this->image, 0, 0, 0, 0, $this->width, $this->height, 50); // center
                imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $this->width, $this->height);

                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $this->width, $this->height - 1, 33.33333); // up
                imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $this->width, $this->height, 25); // down
            }

            imagedestroy($imgCanvas);
            imagedestroy($imgBlur);
            $this->updateInfo();
        }
    }

    protected function _save($file, $quality)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        list($save, $type) = $this->_save_function($extension, $quality);

        // Check if this image is PNG, GIF or WEBP, then set if Transparent
        if (
            in_array($extension, ['jpg','jpeg'])
            && ($this->type == IMAGETYPE_PNG || $this->type == IMAGETYPE_GIF || $this->type == IMAGETYPE_WEBP)
        ) {
            $output = imagecreatetruecolor($this->width, $this->height);
            $white = imagecolorallocate($output, 255, 255, 255);
            imagefilledrectangle($output, 0, 0, $this->width, $this->height, $white);
            imagecopy($output, $this->image, 0, 0, 0, 0, $this->width, $this->height);
            $this->updateInfo($output);
        }

        $status = isset($quality) ? $save($this->image, $file, $quality) : $save($this->image, $file);

        if ($status === true && $type !== $this->type) {
            $this->type = $type;
            $this->mime = image_type_to_mime_type($type);
        }

        return true;
    }

    protected function _save_function($extension, & $quality)
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jfif':
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
            case 'webp':
                $save = 'imagewebp';
                $type = IMAGETYPE_WEBP;
                if ($quality == 100 && version_compare(PHP_VERSION, '8.1', '>=')) {
                    $quality = IMG_WEBP_LOSSLESS;
                }
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
     * @throws waException
     * @return mixed
     */
    protected function _watermark($options)
    {
        $watermark = false;
        $opacity = 0.5;
        $align = self::ALIGN_BOTTOM_RIGHT;
        $font_file = null;
        $font_size = 12;
        $font_color = '888888';
        $text_orientation = self::ORIENTATION_HORIZONTAL;
        extract($options, EXTR_IF_EXISTS);
        $opacity = min(max($opacity, 0), 1);
        imagealphablending($this->image, true);
        if ($watermark instanceof waImage) {

            $type = $watermark->type;
            $gd_watermark = $this->createGDImageResourse($watermark->file, $type);

            $width = ifset($options['width'], $watermark->width);
            $height = ifset($options['height'], $watermark->height);
            $offset = $this->calcWatermarkOffset($width, $height, $align);

            if ($width != $watermark->width || $height != $watermark->height) {
                $watermark_resized = $this->_create($width, $height);
                if (function_exists('imagecopyresampled')) {
                    imagecopyresampled($watermark_resized, $gd_watermark, 0, 0, 0, 0, $width, $height, $watermark->width, $watermark->height);
                } else {
                    imagecopyresized($watermark_resized, $gd_watermark, 0, 0, 0, 0, $width, $height, $watermark->width, $watermark->height);
                }
                imagedestroy($gd_watermark);
                $gd_watermark = $watermark_resized;
            }

            imagecopymerge_alpha($this->image, $gd_watermark, $offset[0], $offset[1], 0, 0, $width, $height, $opacity * 100);
            imagedestroy($gd_watermark);

        } else {
            $text = (string)$watermark;
            if (!$text) {
                return;
            }

            $margin = round($font_size / 3.6);
            $font_color = array(
                'r' => hexdec(substr($font_color, 0, 2)),
                'g' => hexdec(substr($font_color, 2, 2)),
                'b' => hexdec(substr($font_color, 4, 2)),
                'a' => floor((1 - $opacity) * 127)
            );

            if ($align == self::ALIGN_CENTER) {
                $rotation = (int) ifempty($options['rotation'], 0);
                $text_orientation = self::ORIENTATION_HORIZONTAL;
            } else if ($text_orientation == self::ORIENTATION_VERTICAL) {
                $rotation = 90;
            } else {
                $rotation = 0;
            }

            if (!empty($font_file) && file_exists($font_file)) {

                $gd_info = gd_info();
                $gd_version = preg_replace('/[^0-9\.]/', '', $gd_info['GD Version']);

                if (!empty($gd_info['FreeType Support']) && version_compare($gd_version, '2.0.1', '>=')) {
                    // Free Type
                    $free_type = true;
                } else {
                    // True Type
                    $free_type = false;

                    // GD1 use pixels, GD2 use points
                    if (version_compare($gd_version, '2.0', '<')) {
                        $font_size = 24*$font_size/18;    // 24px = 18pt
                    }
                }

                if ($free_type) {
                    $metrics = imageftbbox($font_size, 0, $font_file, $text);
                } else {
                    $metrics = imagettfbbox($font_size, 0, $font_file, $text);
                }
                if ($metrics) {
                    $width = $metrics[2] - $metrics[0];
                    $height = $metrics[1] - $metrics[7];
                    if ($text_orientation == self::ORIENTATION_VERTICAL) {
                        list($width, $height) = array($height, $width);
                    }

                    $offset = $this->calcWatermarkOffset($width, $height, $align, $margin);
                    $offset = $this->watermarkOffsetFix($offset, $width, $height, $align, $text_orientation, $align == self::ALIGN_CENTER ? $rotation : 0);

                    $color = imagecolorallocatealpha($this->image, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                    if ($free_type) {
                        imagefttext($this->image, $font_size, $rotation, $offset[0], $offset[1], $color, $font_file, $text);
                    } else {
                        imagettftext($this->image, $font_size, $rotation, $offset[0], $offset[1], $color, $font_file, $text);
                    }
                } else {
                    throw new waException(_ws("Can't read font file $font_file"));
                }
            } else {
                $font = floor((5 * $font_size) / 12);
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
                $offset = $this->calcWatermarkOffset($width, $height, $align, $margin);
                if ($rotation != 0) {
                    imagestring_rotate($this->image, $font, $rotation, $offset[0], $offset[1], $text, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                } else {
                    $color = imagecolorallocatealpha($this->image, $font_color['r'], $font_color['g'], $font_color['b'], $font_color['a']);
                    imagestring($this->image, $font, $offset[0], $offset[1], $text, $color);
                }
            }
        }
    }

    private function calcWatermarkOffset($width, $height, $align, $margin=10)
    {
        if (is_array($align)) {
            return $align;
        }

        switch ($align) {
            case self::ALIGN_CENTER:
                $offset = array(($this->width - $width) / 2, ($this->height - $height) / 2);
                break;
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
            default:
                $offset = array($this->width - $width - $margin, $this->height - $height - $margin);
                break;
        }
        return $offset;
    }

    private function watermarkOffsetFix($offset, $width, $height, $align, $orientation, $rotation)
    {
        if ($align == self::ALIGN_CENTER) {
            $offset[1] += $height;
            if ($rotation) {
                list($x, $y) = $offset;
                $sin = sin(deg2rad($rotation));
                $cos = cos(deg2rad($rotation));
                $x -= ($width*$cos - $height*$sin - $width)/2;
                $y += ($width*$sin + $height*$cos - $height)/2;
                $offset = array($x, $y);
            }
        } else if ($orientation == self::ORIENTATION_HORIZONTAL) {
            $offset[1] += $height;
            if ($align == self::ALIGN_BOTTOM_LEFT || $align == self::ALIGN_BOTTOM_RIGHT) {
                $offset[1] -= $height/4;
            }
        } else if ($orientation == self::ORIENTATION_VERTICAL) {
            $offset[0] += $width;
            $offset[1] += $height;
            if ($align == self::ALIGN_BOTTOM_RIGHT || $align == self::ALIGN_TOP_RIGHT) {
                $offset[0] -= $width/4;
            }
        }
        return $offset;
    }

    protected function _getPixel($x, $y)
    {
        $color = imagecolorat($this->image, $x, $y);
        if (imageistruecolor($this->image)) {
            $transparency = ($color >> 24) & 0x7F;
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            return array($r/255, $g/255, $b/255, 1 - $transparency/127);
        } else {
            $result = imagecolorsforindex($this->image, $color);
            return array(
                $result['red'] / 255,
                $result['green'] / 255,
                $result['blue'] / 255,
                1 - $result['alpha'] / 127,
            );
        }
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

    $trans_color = imagecolorallocatealpha($cut, 255, 255, 255, 127);
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