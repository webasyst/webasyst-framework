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
     *
     * @param string $file
     * @param bool|string $adapter Gd|Imagick
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
     * @param int $width
     * @param int $height
     * @param string $master
     * @param boolean $deny_exceed_original_sizes
     * @return waImage
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
     *
     * @param int $degrees  (-360;360)
     * @return waImage
     */
    public function rotate($degrees)
    {
        $degrees = (int) $degrees;
        $this->_rotate($degrees);
        return $this;
    }

    /**
     *
     * @param int $width
     * @param int $height
     * @param string $offset_x        int|CENTER|BOTTOM
     * @param string $offset_y     int|CENTER|BOTTOM
     * @param boolean $deny_exceed_original_sizes
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
     *
     * @param string $file
     * @param int $quality
     * @throws waException
     * @return boolean
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
     *
     * @param int $amount  (1;100)
     */
    public function sharpen($amount)
    {
        $amount = min(max($amount, 1), 100);
        $this->_sharpen($amount);
        return $this;
    }

    /**
     * @param string $type waImage FILTER_* constant
     * @param array $params Depends of $type
     *
     * Params
     * waImage::FILTER_CONTRAST
     *   '0'|'level' Level of contrast. 0 is none, 100 is max
     *
     */
    public function filter($type, $params = array())
    {
        $this->_filter($type, $params);
        return $this;
    }

    /**
     * @param array $options
     *     'watermark' => waImage|string. String means text-watermark
     *     'opacity' => float|int 0..1. Fully opaque is 1. Default is 0.3
     *     'align' => self::ALIGN_* const. Default: self::ALIGN_TOP_LEFT
     *     'font_file' => string Path to ttf-font file. Use when text-watermark. Default: default font (in different adapters different default font). Note: use when watermark option is text
     *     'font_size' => float Size of font. Note: use when watermark option is text
     *     'font_color' => string Hex-formatted of color (without #). Default: ffffff. Note: use when watermark option is text
     *     'text_orientation' => self::ORIENTATION_* const. Default: self::ORIENTATION_HORIZONTAL. Note: use when watermark option is text
     *
     * Note: Watermark option is obligatory
     *
     * @return waImage
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
