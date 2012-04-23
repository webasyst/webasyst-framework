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

    const FILTER_GRAYSCALE = 'grayscale';

    const Gd = 'Gd';
    const Imagick = 'Imagick';

    public static $default_adapter = 'Gd';

    //Status adapter
    protected static $checked = false;

    public $width;
    public $height;
    public $file;
    public $type;
    public $ext;

    public function __construct($file)
    {
        try    {
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
            $adapter = self::$default_adapter;
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
     * @return waImage
     */
    public function resize($width = null, $height = null, $master = null)
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
     * @return waImage
     */
    public function crop($width, $height, $offset_x = self::CENTER, $offset_y = self::CENTER)
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
     *
     * @param const $type
     * @return waImage
     */
    public function filter($type)
    {
        $this->_filter($type);
        return $this;
    }

    /**
     *
     * @param waImage|string $watermark. String means text-watermark
     * @param float|int $opacity 0..1. Fully opaque is 1
     * @param null|string path to ttf-font file. Use when text-watermark. If null use default font (in different adapters different default font)
     * @return waImage
     */
    public function watermark($watermark, $opacity = 0.3, $font_file = null)
    {
        $this->_watermark($watermark, $opacity, $font_file);
        return $this;
    }
}
