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

/** Wrapper class for uploaded files.
 * $this->... fields are read only. Correct fields are:
 * name, type, size, tmp_name, error - same as in $_FILES global var.
 * extension - file extension (not including the dot)
 */
class waRequestFile
{

	const OK	= 0;
	const INI_SIZE	= 1;
	const FORM_SIZE	= 2;
	const PARTIAL	= 3;
	const NO_FILE	= 4;
	const NO_TMP_DIR	= 6;
	const CANT_WRITE	= 7;
	const EXTENSION	= 8;
	/** $this->data === null represents a case when no file is uploaded. */
	protected $data = array(
        'name' => '',
        'type' => '',
        'size' => 0,
        'tmp_name' => null,
        'error' => 'no file uploaded',
	);

	public function __construct($data) {
		if ($data === null) {
			$this->data = null;
			return;
		}

		foreach($this->data as $k => $v) {
			if (!isset($data[$k])) {
				throw new waException("Key {$k} must be set.");
			}
		}
		$this->setData($data['name'], $data['type'], $data['size'], $data['tmp_name'], $data['error']);
	}

	public function uploaded() {
		return ($this->data !== null)&&(!$this->data['error']);
	}

	/**
	 * Return waImage class for this image ot null if image is broken
	 *
	 * @return waImage
	 */
	public function waImage() {
		if ($this->data === null) {
			throw new waException('No file uploaded.');
		}
		return waImage::factory($this->data['tmp_name']);
	}

	/** When 2-nd parameter is omitted, first one is considered to be full path */
	public function moveTo($dir, $name=null) {
		return move_uploaded_file($this->data['tmp_name'], $this->concatFullPath($dir, $name));
	}

	/** When 2-nd parameter is omitted, first one is considered to be full path */
	public function copyTo($dir, $name=null) {
		return copy($this->data['tmp_name'], $this->concatFullPath($dir, $name));
	}

	public function __get($name) {
		switch($name) {
			case 'extension':
				$path_info = pathinfo($this->data['name']);
				return $path_info['extension'];
			default: // is it a key in $this->data?
				if ($this->data === null) {
					throw new waException('No file uploaded.');
				}

				if (!isset($this->data[$name])) {
					throw new waException('Unable to read '.$name);
				}
				return $this->data[$name];
		}
	}

	public function __set($name, $value) {
		throw new waException('waRequestFile fields are read-only.');
	}

	public function __isset($name) {
		if ($this->data === null) {
			throw new waException('No file uploaded.');
		}

		return isset($this->data[$name]);
	}

	public function __toString() {
		if ($this->data === null) {
			return '';
		}

		// full path to temporary file
		return $this->data['tmp_name'];
	}

	//
	// non-public methods
	//
	protected function setData($name, $type, $size, $tmp_name, $error) {
		if (!is_int($error)) {
			throw new waException('File error code must be integer.');
		}
		if($error!=self::OK){
			switch ($error){
				case self::INI_SIZE:$error='Target file exceeds maximum allowed size. (upload_max_filesize)';break;
				case self::FORM_SIZE:$error='Target file exceeds the MAX_FILE_SIZE value specified on the upload form.';break;
				case self::PARTIAL:$error='Target file was not uploaded completely.';break;
				case self::NO_FILE:$error='No target file was uploaded.';break;
				case self::NO_TMP_DIR:$error='Missing a temporary folder.';break;
				case self::CANT_WRITE:$error='Failed to write target file to disk.';break;
				case self::EXTENSION:$error='File upload stopped by extension.';break;
				default:$error='Unknown upload error';break;
			}
		}else{

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
			if(!is_uploaded_file($tmp_name)){
				throw new waException('Possible file upload attack: '.$tmp_name);
			}
			if (!file_exists($tmp_name)) {
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

	/** used by moveTo() and copyTo() */
	protected function concatFullPath($dir, $name) {
		if ($this->data === null) {
			throw new waException('No file uploaded.');
		}
		if (!file_exists($this->data['tmp_name'])) {
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

// EOF