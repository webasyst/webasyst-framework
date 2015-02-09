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
class waImage
{
    const NONE    = 'NONE';
    const AUTO    = 'AUTO';
    const INVERSE = 'INVERSE';
    const WIDTH   = 'WIDTH';
    const HEIGHT  = 'HEIGHT';

    const CENTER = 'CENTER';
    const BOTTOM = 'BOTTOM';

    const ALIGN_TOP_LEFT     = 'ALIGN_TOP_LEFT';
    const ALIGN_TOP_RIGHT    = 'ALIGN_TOP_RIGHT';
    const ALIGN_BOTTOM_LEFT  = 'ALIGN_BOTTOM_LEFT';
    const ALIGN_BOTTOM_RIGHT = 'ALIGN_BOTTOM_RIGHT';

    const ORIENTATION_VERTICAL = 'VERTICAL';
    const ORIENTATION_HORIZONTAL = 'HORIZONTAL';

    const FILTER_GRAYSCALE = 'GRAYSCALE';
    const FILTER_SEPIA = 'SEPIA';
    const FILTER_CONTRAST = 'CONTRAST';
    const FILTER_BRIGHTNESS = 'BRIGHTNESS';

    const Gd = 'Gd';
    const Imagick = 'Imagick';

    public static $default_adapter;

    //Status adapter
    protected static $checked = false;

    public $width;
    public $height;
    public $file;
    public $type;
    public $ext;

    public function __construct($file)
    {
        try {
            $file = realpath($file);
            $image_info = @getimagesize($file);
        }
        catch (Exception $e){}
        if (empty($file) OR empty($image_info)) {
            if(!preg_match('//u', $file)) {
                $file = iconv('windows-1251','utf-8',$file);
            }
            throw new waException(_ws('Not an image or invalid image: ').$file);
        }
        $this->file   = $file;
        $this->width  = $image_info[0];
        $this->height = $image_info[1];
        $this->type   = $image_info[2];
        $this->mime   = image_type_to_mime_type($this->type);

    }

    /**
     * Returns name extension of a graphical file corresponding to its type.
     *
     * @return string|null Returns null if image type is not JPEG, GIF, or PNG.
     */
    public function getExt()
    {
        switch ($this->type)
        {
            case IMAGETYPE_JPEG: {
                return 'jpg';
                break;
            }
            case IMAGETYPE_GIF: {
                return 'gif';
                break;
            }
            case IMAGETYPE_PNG: {
                return 'png';
                break;
            }
        }
        return null;
    }

    private static function getDefaultAdapter()
    {
        $adapter = null;
        $adapters = array();
        if (self::$default_adapter) {
            $adapters[] = self::$default_adapter;
        }
        $adapters[] = self::Imagick;
        $adapters[] = self::Gd;
        foreach($adapters as $adapter) {
            if (extension_loaded(strtolower($adapter))) {
                break;
            } else {
                $adapter = null;
            }
        }
        return $adapter;
    }

    /**
     * Returns an instance of waImage class for processing specified image file.
     *
     * @param string $file Path to image file to be processed
     * @param bool|string $adapter Optional name of image processing adapter (PHP extension): Gd or Imagick.
     *     If not specified, Imagick is used by default. If not available, Gd is used.
     * @throws waException
     * @return waImage
     */
    public static function factory($file, $adapter = false)
    {
        if (!$adapter) {
            $adapter = self::getDefaultAdapter();
        }

        $class = 'waImage'.$adapter;

        if (!class_exists($class, true)) {
            throw new waException(sprintf(_ws('Not %s image adapter'), $adapter));
        }
        return new $class($file);
    }

    /**
     * Resizes an image.
     *
     * @param int $width Required image width in pixels.
     * @param int $height Required image width.
     * @param string $master Parameter denoting which of the image dimensions (width or height) should be used
     *     as the basic one to calculate the size of the other dimension. If not specified, then AUTO value is used by
     *     default. Acceptable values:
     *
     *     'NONE': If no value for $width parameter is specified, then with NONE, the width of the thumbnail will be
     *     equal to that of the original image; if no value for $height parameter is specified, then with NONE, the
     *     height of the thumbnail will be equal to that of the original image.
     *
     *     'AUTO': If the ratio of the original image width to the thumbnail width is greater than the ratio of the
     *     original height to the thumbnail height, then WIDTH is used as the value of the $master parameter;
     *     otherwise HEIGHT is used.
     *
     *     'INVERSE': If the ratio of the original image width to the thumbnail width is greater than the ratio of the
     *     original height to the thumbnail height, then HEIGHT is used as the value of the $master parameter;
     *     otherwise WIDTH is used.
     *
     *     'WIDTH': The height of the thumbnail is calculated based on the width of the original image with proportions
     *     maintained; if any value is specified for the $height parameter, it is ignored.
     *
     *     'HEIGHT': The width of the thumbnail is calculated based on the height of the original image with proportions
     *     maintained; if any value is specified for the $width parameter, it is ignored.
     * @param boolean $deny_exceed_original_sizes Flag allowing the dimensions of the cropped image part to exceed
     *     those of the original image. If not specified, true is used by default.
     * @return waImage Processed image object
     */
    public function resize($width = null, $height = null, $master = null, $deny_exceed_original_sizes = true)
    {
        if (!$master)
        {
            $master = self::AUTO;
        }
        elseif ($master == self::WIDTH && !empty($width))
        {
            $master = self::AUTO;
            $height = null;
        }
        elseif ($master == self::HEIGHT && ! empty($height))
        {
            $master = self::AUTO;
            $width = null;
        }

        if (empty($width))
        {
            if ($master === self::NONE)
            {
                $width = $this->width;
            }
            else
            {
                $master = self::HEIGHT;
            }
        }

        if (empty($height))
        {
            if ($master === self::NONE)
            {
                $height = $this->height;
            }
            else
            {
                $master = self::WIDTH;
            }
        }

        switch ($master)
        {
            case self::AUTO:
                {
                    $master = ($this->width / $width) > ($this->height / $height) ? self::WIDTH : self::HEIGHT;
                    break;
                }
            case self::INVERSE:
                {
                    $master = ($this->width / $width) > ($this->height / $height) ? self::HEIGHT : self::WIDTH;
                    break;
                }
        }

        switch ($master)
        {
            case self::WIDTH:
                {
                    $height = $this->height * $width / $this->width;
                    break;
                }
            case self::HEIGHT:
                {
                    $width = $this->width * $height / $this->height;
                    break;
                }
        }

        $width  = max(round($width), 1);
        $height = max(round($height), 1);

        if ($deny_exceed_original_sizes && ($width > $this->width || $height > $this->height)) {
            return $this;
        }
        if ($width == $this->width && $height == $this->height) {
            return $this;
        }

        $this->_resize($width, $height);

        return $this;
    }

    /**
     * Rotates image.
     *
     * @param int $degrees Integer value of the rotation degree from -360 to 360. Positive values mean clockwise
     *     rotation, negative - counterclockwise.
     * @return waImage Rotated image object
     */
    public function rotate($degrees)
    {
        $degrees = (int) $degrees;
        $this->_rotate($degrees);
        return $this;
    }

    /**
     * Crops specified image part.
     *
     * @param int $width Cropped portion width
     * @param int $height Cropped portion height
     * @param int|string $offset_x Offset of the cropped image portion to the right of the left edge of the original
     *     image, in pixels. Optionally, these constants can be specified:
     *     waImage::CENTER - crop central image part
     *     waImage::BOTTOM - crop rightmost image part
     *     If not specified, central image part will be cropped.
     * @param int|string $offset_y Offset of the cropped image portion to the bottom of the top edge of the original
     *     image, in pixels. Optionally, these constants can be specified:
     *     waImage::CENTER - crop middle image part
     *     waImage::BOTTOM - crop bottom image part
     *     If not specified, middle image part will be cropped.
     * @param bool $deny_exceed_original_sizes Flag allowing the dimensions of the cropped image part to exceed those
     *     of the original image. Default value: true.
     * @return waImage
     */
    public function crop($width, $height, $offset_x = self::CENTER, $offset_y = self::CENTER, $deny_exceed_original_sizes = true)
    {
        $width = ($width > $this->width) ? $this->width : $width;
        $height = ($height > $this->height) ? $this->height : $height;

        if ($offset_x === self::CENTER)
        {
            //Center
            $offset_x = round(($this->width - $width) / 2);
        }
        elseif ($offset_x === self::BOTTOM)
        {
            //Bottom
            $offset_x = $this->width - $width;
        }
        elseif ($offset_x < 0)
        {
            $offset_x = $this->width - $width + $offset_x;
        }

        if ($offset_y === self::CENTER)
        {
            //Center
            $offset_y = round(($this->height - $height) / 2);
        }
        elseif ($offset_y === self::BOTTOM)
        {
            //Bottom
            $offset_y = $this->height - $height;
        }
        elseif ($offset_y < 0)
        {
            $offset_y = $this->height - $height + $offset_y;
        }

        $max_width  = $this->width  - $offset_x;
        $max_height = $this->height - $offset_y;

        if ($width > $max_width)
        {
            $width = $max_width;
        }

        if ($height > $max_height)
        {
            $height = $max_height;
        }

        if ($deny_exceed_original_sizes && ($width > $this->width || $height > $this->height)) {
            return $this;
        }
        if ($width == $this->width && $height == $this->height) {
            return $this;
        }
        $this->_crop($width, $height, $offset_x, $offset_y);

        return $this;
    }

    /**
     * Saves image with specified quality level to file.
     *
     * @param string $file Path at which new image must be created. If not specified, image will be saved to original file.
     * @param int $quality Image quality percentage from 1 (worst quality) to 100 (best quality, default value).
     * @throws waException
     * @return bool Whether saved successfully
     */
    public function save($file = null, $quality = 100)
    {
        if (!$file)    {
            $file = $this->file;
        }
        if (is_file($file))    {
            if (!is_writable($file)) {
                if(!preg_match('//u', $file)) {
                    $file = iconv('windows-1251','utf-8',$file);
                }
                throw new waException(_ws('File must be writable: ').$file);
            }
        }

        $quality = min(max($quality, 1), 100);
        return $this->_save($file, $quality);
    }

    /**
     * Applies sharpening filter to image.
     *
     * @param float $amount Filter strength from 1 to 100.
     * @return Processed image object
     */
    public function sharpen($amount)
    {
        $amount = min(max($amount, 1), 100);
        $this->_sharpen($amount);
        return $this;
    }

    /**
     * Applies a graphical filter to image.
     *
     * @param string $type Filter type, one of waImage constants:
     *     FILTER_GRAYSCALE
     *     FILTER_SEPIA
     *     FILTER_CONTRAST
     *     FILTER_BRIGHTNESS
     * @param array $params Extra parameters for applying filters FILTER_CONTRAST and FILTER_BRIGHTNESS:
     *     array($level) or array('level' => $level) - filter strength from 0 to 100 (0 - no filtering, 100 - max level)
     *     If not specified, filter strength level 3 is used by default.
     * @return waImage Processed image object
     */
    public function filter($type, $params = array())
    {
        $this->_filter($type, $params);
        return $this;
    }

    /**
     * Adds a watermark to image.
     *
     * @param array $options Associative array of watermark options with the following keys:
     *     'watermark': (required option) Instance of waImage class containing information about an image or a text string
     *     'opacity': Fractional value of watermark opacity from 0 to 1. Maximum transparency corresponds to 0. If not
     *     specified, default value is 0.3.
     *     align: String identifier of text alignment relative to the original image (default value is ALIGN_TOP_LEFT).
     *     Acceptable values:
     *         'ALIGN_TOP_LEFT'
     *         'ALIGN_TOP_RIGHT'
     *         'ALIGN_BOTTOM_LEFT'
     *         'ALIGN_BOTTOM_RIGHT'
     *     font_file: Path to font file. If not specified, or if the specified font file is missing, default font built
     *     in PHP is used.
     *     font_size: Font size. If not specified, default value of 14 is used.
     *     font_color: 6-digit color value. If not specified, 000000 is used by default.
     *     text_orientation: String identifier of text orientation. Acceptable values:
     *         VERTICAL
     *         HORIZONTAL: this value is used if text orientation is not specified.
     * @return waImage Processed image object
     */
    public function watermark($options)
    {
        if (!isset($options['watermark'])) {
            throw new waException("'watermark' is Obligatory option");
        }
        $options['opacity'] = !empty($options['opacity']) ? $options['opacity'] : 0.3;
        $options['align'] = !empty($options['align']) ? $options['align'] : self::ALIGN_TOP_LEFT;
        if (!($options['watermark'] instanceof waImage)) {
            $options['font_file'] = !empty($options['font_file']) ? $options['font_file'] : null;
            $options['font_size'] = !empty($options['font_size']) ? $options['font_size'] : 14;
            $options['font_color'] = !empty($options['font_color']) ? ltrim($options['font_color'], '#') : '000000';
            if (strlen($options['font_color']) < 6) {
                $options['font_color'] = str_pad($options['font_color'], 6, '0');
            }
            $options['text_orientation'] = !empty($options['text_orientation']) ? $options['text_orientation'] : self::ORIENTATION_HORIZONTAL;
        }
        $this->_watermark($options);
        return $this;
    }
}
