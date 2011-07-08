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
 * @subpackage files
 */
class waFiles
{
	public static function create($path)
	{
		if (file_exists($path)) {
			return $path;
		}
		$return_path = $path;
		if (substr($path, -1) !== '/' && strpos(basename($path), ".") !== false) {
			$path = dirname($path);
		}
		if (!file_exists($path)) {
			@mkdir($path, 0775, true);
			if (!file_exists($path)) {
				//@todo: Recursive create dirictory
			}
		}
		return $return_path;
	}

	public static function copy($source_path, $dest_path)
	{
		if (is_dir($source_path)) {

		} else {
			self::create($dest_path);
			copy($source_path, $dest_path);
		}
	}

	public static function move($source_path, $dest_path)
	{
		if (is_dir($source_path)) {

		} else {
			self::create($dest_path);
			rename($source_path, $dest_path);
		}
	}

	public static function write($source_path, $content)
	{
		$h = fopen($source_path, "w+");
		if ($h) {
			fwrite($h, $content);
			fclose($h);
		}
	}

	public static function listdir($dir) {
		if (!file_exists($dir) || !is_dir($dir)) {
			return array();
		}

		if (! ( $dh = opendir($dir))) {
			return array();
		}

		$result = array();
		while (false !== ( $file = readdir($dh))) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			$result[] = $file;
		}
		closedir($dh);
		return $result;
	}

	public static function delete($path, $skip_dir = false)
	{
		if (!file_exists($path)) {
			return true;
		}
		$result = true;
		if (is_dir($path)) {
			try{
				if(file_exists($path)){
					$dir=opendir($path);
					while (false!==($current_path=readdir($dir))){
						if(($current_path != '.' )&&($current_path != '..')){
							$result &= self::delete($path.'/'.$current_path, $skip_dir);
						}
					}
					closedir($dir);
					$result &= @rmdir($path)||$skip_dir;
				}
			}catch(Exception $ex){
				if($dir&&is_resource($dir)){
					closedir($dir);
				}
				throw $ex;
			}

		} else {
			$result = @unlink($path);
		}
		return $result;
	}


	public static function getMimeType($filename)
	{
		if (preg_match('/\.([^.]*?)$/', $filename, $match)) {
			$type = strtolower($match[1]);
		} else {
			$type = '';
		}

		switch ($type) {
			case 'jpg': case 'jpeg': case 'jpe': return 'image/jpeg';
			case 'png': case 'gif': case 'bmp': case 'tiff' : return 'image/'.strtolower($match[1]);

			case 'doc': case 'docx': return 'application/msword';
			case 'xls': case 'xlt': case 'xlm': case 'xld': case 'xla': case 'xlc': case 'xlw': case 'xll': return 'application/vnd.ms-excel';
			case 'ppt': case 'pps': return 'application/vnd.ms-powerpoint';
			case 'rtf': return 'application/rtf';
			case 'txt': return 'text/plain';
			case 'csv': return 'text/csv;  charset=utf-8';
			case 'pdf': return 'application/pdf';
			case 'html': case 'htm': case 'php': return 'text/html';
			case 'zip': return 'application/zip';
			case 'tar': return 'application/x-tar';
			case 'js': return 'application/x-javascript';
			case 'json': return 'application/json';
			case 'css': return 'text/css';
			case 'xml': return 'application/xml';
			case 'mpeg': case 'mpg': case 'mpe': return 'video/mpeg';
			case 'mp3': return 'audio/mpeg3';
			case 'wav': return 'audio/wav';
			case 'aiff': case 'aif': return 'audio/aiff';
			case 'avi': return 'video/msvideo';
			case 'wmv': return 'video/x-ms-wmv';
			case 'mov': return 'video/quicktime';
			case 'zip': return 'application/zip';
			case 'tar': return 'application/x-tar';
			case 'swf': return 'application/x-shockwave-flash';

			default: return 'application/octet-stream';
		}
	}

	/**
	 * Send headers, print file and exit
	 *
	 * @param string $file - full path to file
	 * @param string $attach - if not null, file will be sent as attachment using $attache as name of the file in headers
	 * @param boolean $exit - if true exit() will be called at end
	 * @param boolean $md5 - if true will be sent Content-MD5 header
	 * @throws waException - if file does not exists
	 */
	public static function readFile($file, $attach = null, $exit = true, $md5 = false)
	{
		if (file_exists($file)) {
			$file_type = self::getMimeType($attach ? $attach : $file);
			if($md5) {
				$md5 = base64_encode(md5_file($file));
			}
			@ini_set( 'async_send', 1 );
			$sid = wa()->getStorage()->close();

			if ($attach !== null) {
				$send_as = str_replace('"', '\"', $attach);


				//TODO detect nginx internal redirect available
				$allow_accel_redirect = false;

				$file_size = filesize($file);
				if(!$allow_accel_redirect){
					$from = $to = false;

					if(isset($_SERVER['HTTP_RANGE']) && $_SERVER['HTTP_RANGE'] ) {
						list($dimension, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
						$ranges = explode(',',$range);
						$intervals = array();
						foreach($ranges as $range){
							$range = trim($range);
							if(preg_match('/^(\d+)-(\d+)$/',$range,$matches)) {
								$intervals[] = array(
								'from'	=>intval($matches[1]),
								'to'	=>intval($matches[2])
								);
							}elseif(preg_match('/^(\d+)-$/',$range,$matches)) {
								$intervals[] = array(
								'from'	=>intval($matches[1]),
								'to'	=>$file_size-1
								);

							}elseif(preg_match('/^-(\d+)$/',$range,$matches)) {
								$intervals[] = array(
								'from'	=>$file_size-intval($matches[1]),
								'to'	=>$file_size-1
								);
							}else{
								throw new waException('Requested range not satisfiable',416);
							}
						}

						foreach($intervals as $interval) {
							if($from === false){
								$from = $interval['from'];
							}
							if($to === false){
								$to = $interval['to'];
							}else{
								if(($to+1)==$interval['from']){
									$to = $interval['to'];
								}else{
									//hole at interval
									throw new waException('Requested range not satisfiable',416);
								}
							}
						}

						if($from<0 || ($to+1)>$file_size){
							throw new waException('Requested range not satisfiable',416);
						}

						$range_length = $to-$from+1;
						header("HTTP/1.1 206 Partial Content");
						header("Content-Length: {$range_length}");
						header("Content-Range: bytes {$from}-{$to}/{$file_size}");
					} else {
						header("Content-Length: {$file_size}");
						if($md5){
							header("Content-MD5: {$md5}");
						}
					}

					header("Cache-Control: no-cache, must-revalidate");
					header("Content-type: {$file_type}");
					header("Content-Disposition: attachment; filename=\"{$send_as}\";");

					header("Accept-Ranges: bytes");
					header('Connection: close');

					$fp = fopen($file, 'rb');
					if($from){
						fseek($fp,$from);
					}

					$chunk = 1048576;//1M
					while (!feof($fp) && $chunk && (connection_status()==0)) {
						if($to) {
							$chunk = min(1+$to-@ftell($fp),$chunk);
						}
						if($chunk) {
							print @fread($fp, $chunk );
							@flush();
						}
					}

					@fclose($fp);
				}else { //internal nginx redirect
					$path = substr($file, strlen($nginx_path));
					$path = preg_replace('@([/\\\\]+)@','/','/'.$nginx_base.'/'.$path);

					header("Content-type: {$file_type}");
					header("Content-Disposition: attachment; filename=\"{$send_as}\";");
					header("Accept-Ranges: bytes");
					header("Content-Length: {$file_size}");
					header("Expires: 0");
					header("Cache-Control: no-cache, must-revalidate");
					header("Pragma: public");
					header("Connection: close");
					if($md5){
						header("Content-MD5: {$md5}");
					}

					header("X-Accel-Redirect: {$path}");
				}
			}else{
				header("Content-type: {$file_type}");
				if($md5){
					header("Content-MD5: {$md5}");
				}
				@readfile($file);
			}
			if ($exit) {
				exit();
			}elseif($sid) {
				wa()->getStorage()->open();
			}
		} else {
			throw new waException("File not found", 404);
		}
	}
}

