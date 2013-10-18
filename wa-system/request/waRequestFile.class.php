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
 * @subpackage request
 */

/**
 * Wrapper class for uploaded files.
 *
 * $this->... fields are read only.
 * Correct fields are:
 * name, type, size, tmp_name, error, error_code - same as in $_FILES global var.
 * extension - file extension (not including the dot)
 *
 * @property string $name
 * @property string $type
 * @property int $size
 * @property string $tmp_name
 * @property string $error
 * @property string $extension
 */
class waRequestFile
{
    /** Set this to true for $this->setData() not to check file with is_uploaded_file() */
    public $skip_uploaded_check = false;

    /**
     * $this->data === null represents a case when no file is uploaded.
     */
    protected $data = array(
        'name' => '',
        'type' => '',
        'size' => 0,
        'tmp_name' => null,
        'error' => 'no file uploaded',
    );

    public function __construct($data, $skip_uploaded_check = false)
    {
        if ($data === null) {
            $this->data = null;
            return;
        }
        foreach($this->data as $k => $v) {
            if (!isset($data[$k])) {
                throw new waException("Key {$k} must be set.");
            }
        }
        $this->skip_uploaded_check = $skip_uploaded_check;
        $this->setData($data['name'], $data['type'], $data['size'], $data['tmp_name'], $data['error']);
    }

    public function uploaded()
    {
        return ($this->data !== null) && !$this->data['error'];
    }

    /**
     * Return waImage class for this image ot null if image is broken
     *
     * @throws waException
     * @return waImage
     */
    public function waImage()
    {
        if ($this->data === null) {
            throw new waException('No file uploaded.');
        }
        return waImage::factory($this->data['tmp_name']);
    }

    /**
     * When 2-nd parameter is omitted, first one is considered to be full path
     * @param $dir
     * @param string $name
     * @return bool
     */
    public function moveTo($dir, $name = null)
    {
        if (@is_uploaded_file($this->data['tmp_name'])) {
            return @move_uploaded_file($this->data['tmp_name'], $this->concatFullPath($dir, $name));
        } else {
            return @rename($this->data['tmp_name'], $this->concatFullPath($dir, $name));
        }
    }

    /** When 2-nd parameter is omitted, first one is considered to be full path */
    public function copyTo($dir, $name=null)
    {
        return @copy($this->data['tmp_name'], $this->concatFullPath($dir, $name));
    }

    public function __get($name)
    {
        switch($name) {
            case 'extension':
                $path_info = pathinfo($this->data['name']);
                return isset($path_info['extension']) ? $path_info['extension'] : '';
            default: // is it a key in $this->data?
                if ($this->data === null) {
                    throw new waException('No file uploaded.');
                }
                if ($name == 'error_code') {
                    return $this->data['error'];
                }
                if (!isset($this->data[$name])) {
                    throw new waException('Unable to read '.$name);
                }
                if ($name == 'error') {
                    return self::getError($this->data['error']);
                }
                return $this->data[$name];
        }
    }

    public function __set($name, $value)
    {
        throw new waException('waRequestFile fields are read-only.');
    }

    public function __isset($name)
    {
        if ($this->data === null) {
            throw new waException('No file uploaded.');
        }

        return isset($this->data[$name]);
    }

    public function __toString()
    {
        if ($this->data === null) {
            return '';
        }

        // full path to temporary file
        return $this->data['tmp_name'];
    }

    //
    // non-public methods
    //
    protected function setData($name, $type, $size, $tmp_name, $error)
    {
        if (!is_int($error)) {
            throw new waException('File error code must be integer.');
        }
        if ($error == UPLOAD_ERR_OK) {
            if (!is_string($name)) {
                throw new waException('File input name must be string.');
            }
            if (!is_string($type)) {
                throw new waException('File type must be string.');
            }
            if (!is_int($size)) {
                throw new waException('File size must be integer.');
            }
            if (!is_string($tmp_name)) {
                throw new waException('File tmp_name must be string.');
            }
            if (!$this->skip_uploaded_check && !is_uploaded_file($tmp_name)) {
                throw new waException('Possible file upload attack: '.$tmp_name);
            } else if ($this->skip_uploaded_check && !file_exists($tmp_name)) {
                throw new waException('No such file ($tmp_name): '.$tmp_name);
            }
        }
        $this->data = array(
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'tmp_name' => $tmp_name,
            'error' => $error,
        );
    }

    public static function getError($error)
    {
        switch ($error) {
            case UPLOAD_ERR_OK:
                return '';
            case UPLOAD_ERR_INI_SIZE:
                return _ws('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
            case UPLOAD_ERR_FORM_SIZE:
                return _ws('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
            case UPLOAD_ERR_PARTIAL:
                return _ws('The uploaded file was only partially uploaded.');
            case UPLOAD_ERR_NO_FILE:
                return _ws('No file was uploaded.');
            case UPLOAD_ERR_NO_TMP_DIR:
                return _ws('Missing a temporary folder.');
            case UPLOAD_ERR_CANT_WRITE:
                return _ws('Failed to write file to disk.');
            case UPLOAD_ERR_EXTENSION:
                return _ws('A PHP extension stopped the file upload.');
            default:
                return _ws('Unknown upload error');
        }
    }

    /**
     * Returns full path to the file
     * Used by methods moveTo() and copyTo()
     *
     * @param string $dir - directory
     * @param string $name - name of the file
     * @return string - full path
     */
    protected function concatFullPath($dir, $name)
    {
        if ($this->data === null) {
            throw new waException('No file uploaded.');
        }

        if (!$this->skip_uploaded_check && !is_uploaded_file($this->data['tmp_name'])) {
            throw new waException('Temporary file does not exist anymore.');
        } else if ($this->skip_uploaded_check && !file_exists($this->data['tmp_name'])) {
            throw new waException('Temporary file does not exist anymore.');
        }

        if ($name === null) {
            return $dir;
        } else {
            $lastSym = substr($dir, -1);
            if ($lastSym == '/' || $lastSym == '\\') {
                return $dir.$name;
            } else {
                return $dir.'/'.$name;
            }
        }
    }
}
