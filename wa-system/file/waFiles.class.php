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

/**
 * Collection of helper functions to work with files.
 */
class waFiles
{
    private static $fp;
    private static $size;

    private function __construct()
    {
        throw new waException('waFiles::__construct disabled');
    }
    /**
     * Create parent directories for given file path, unless already exist.
     *
     * @param string $path  full path
     * @return string|bool copy of $path if success or false otherwise
     */
    public static function create($path)
    {
        if (file_exists($path)) {
            return $path;
        }
        $result = $path;
        if (substr($path, -1) !== '/' && strpos(basename($path), ".") !== false) {
            $path = dirname($path);
        }
        if ($path && !file_exists($path)) {
            $status = @mkdir($path, 0775, true);
            if (!file_exists($path) && file_exists(self::create(dirname($path)))) {
                $status = @mkdir($path, 0775, true);
            }
            if (!$status) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Copy a file, creating parent directories if needed.
     * @param string $source_path full path to source file
     * @param string $dest_path full patn to destination file
     * @param string|array $skip_pattern pattern to skip files
     * @throws Exception
     */
    public static function copy($source_path, $dest_path, $skip_pattern = null)
    {
        if (is_dir($source_path)) {
            try {
                if ($dir = opendir($source_path)) {
                    self::create($dest_path);
                    while (false !== ($path = readdir($dir))) {
                        if (($path != '.') && ($path != '..')) {
                            $destination = $dest_path.'/'.$path;
                            $source = $source_path.'/'.$path;
                            if ($skip_pattern) {
                                foreach ((array) $skip_pattern as $pattern) {
                                    if (preg_match($pattern, $source)) {
                                        continue 2;
                                    }
                                }
                            }
                            if (file_exists($source)) {
                                if (!is_dir($source) && file_exists($destination)) { //skip file move on resume
                                    self::delete($destination);
                                }
                                self::copy($source, $destination, $skip_pattern);
                            } else {
                                throw new Exception("Not found {$source_path}/{$path}");
                            }
                        }
                    }
                    closedir($dir);
                }
            } catch (Exception $e) {
                if ($dir && is_resource($dir)) {
                    closedir($dir);
                }
                throw $e;
            }
        } else {
            self::create(dirname($dest_path));
            if (@copy($source_path, $dest_path)) {
                /*@todo copy file permissions*/
            } else {
                if (file_exists($source_path) && file_exists($dest_path) && (filesize($source_path) === 0)) {
                    /*It's ok - it's windows*/
                } else {
                    throw new Exception("error on copy from {$source_path} to {$dest_path}");
                }
            }
        }
    }

    /**
     * Move (rename) a file, creating parent directories if needed.
     * @param string $source_path full path to source file
     * @param string $dest_path full patn to destination file
     * @return bool
     */
    public static function move($source_path, $dest_path)
    {
        self::create(dirname($dest_path));
        return rename($source_path, $dest_path);
    }

    /**
     * Create file if not exists or empty if exists and write content into the file.
     * @param string $path full path to file
     * @param string $content data to write
     */
    public static function write($path, $content)
    {
        self::create(dirname($path));
        $h = fopen($path, "w+");
        if ($h) {
            fwrite($h, $content);
            fclose($h);
        }
    }

    /**
     * @param string $dir
     * @param bool $recursive
     * @return array list of all files in given directory
     */
    public static function listdir($dir, $recursive = false)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return array();
        }

        if (!($dh = opendir($dir))) {
            return array();
        }

        $result = array();
        while (false !== ($file = readdir($dh))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if ($recursive && is_dir($dir.'/'.$file)) {
                $files = self::listdir($dir.'/'.$file, $recursive);
                foreach ($files as $sub_file) {
                    $result[] = $file.'/'.$sub_file;
                }
            } else {
                $result[] = $file;
            }
        }
        closedir($dh);
        return $result;
    }

    /**
     * Delete file or recursively delete a directory.
     *
     * @param string $path full path to file
     * @param boolean $ignore_dir_errors true to silently skip errors when deleting directories supposed to be empty (defaults to false)
     * @throws waException
     * @return bool
     */
    public static function delete($path, $ignore_dir_errors = false)
    {
        if (!$path || !file_exists($path)) {
            return true;
        }

        // if it's a file then simply delete it
        if (!is_dir($path)) {
            if (!@unlink($path)) { // && (sleep(1) || !@unlink($path))
                throw new waException('Unable to delete file: '.$path);
            }
            return true;
        }

        // recursively delete a directory
        try {
            if ( ( $dir = opendir($path))) {
                while (false !== ($current_path = readdir($dir))) {
                    if ($current_path === null) {
                        break; // being paranoid
                    }
                    if (($current_path != '.') && ($current_path != '..')) {
                        self::delete($path.'/'.$current_path, $ignore_dir_errors);
                    }
                }
                closedir($dir);
            }
            if (!@rmdir($path) && !$ignore_dir_errors) {
                throw new waException('Unable to delete directory: '.$path);
            }
            return true;
        } catch (Exception $ex) {
            if (!empty($dir) && is_resource($dir)) {
                closedir($dir);
            }
            throw $ex;
        }
    }

    public static function extension($file)
    {
        if (($i = strrpos($file, '.')) !== false) {
            return strtolower(substr($file, $i + 1));
        }
        return '';
    }

    /**
     * Determine MIME type by filename (using extension)
     * @param string $filename
     * @return string
     */
    public static function getMimeType($filename)
    {
        $type = self::extension($filename);

        switch ($type) {
            case 'jpg': case 'jpeg': case 'jpe': return 'image/jpeg';
            case 'png': case 'gif': case 'bmp': case 'tiff': return 'image/'.strtolower($type);
            case 'ico': return 'image/x-icon';
            case 'doc': case 'docx': return 'application/msword';
            case 'xls': case 'xlt': case 'xlm': case 'xld': case 'xla': case 'xlc': case 'xlw': case 'xll': return 'application/vnd.ms-excel';
            case 'ppt': case 'pps': return 'application/vnd.ms-powerpoint';
            case 'rtf': return 'application/rtf';
            case 'txt': return 'text/plain';
            case 'csv': return 'text/csv;  charset=utf-8';
            case 'pdf': return 'application/pdf';
            case 'html': case 'htm': case 'php': return 'text/html';
            case 'js': return 'application/x-javascript';
            case 'json': return 'application/json';
            case 'css': return 'text/css';
            case 'dtd': return 'application/xml-dtd';
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
            case 'eml': return 'message/rfc822';

            default: return 'application/octet-stream';
        }
    }

    /**
     *
     * Change file encoding
     *
     * @param string $file file path
     * @param string $from original file encoding
     * @param string $to target file encoding
     * @throws waException
     * @return string converted file path
     */
    public static function convert($file, $from, $to = 'UTF-8')
    {
        if ($src = fopen($file, 'rb')) {
            $filter = sprintf('convert.iconv.%s/%s//IGNORE', $from, $to);
            if (!@stream_filter_prepend($src, $filter)) {
                throw new waException("error while register file filter");
            }
            $file = preg_replace('/(\.[^\.]+)$/', '.'.$to.'$1', $file);
            if ($src && ($dst = fopen($file, 'wb'))) {
                stream_copy_to_stream($src, $dst);
                fclose($src);
                fclose($dst);
            } else {
                throw new waException("Error while convert file encoding");
            }
            return $file;
        } else {
            return false;
        }
    }

    private static function curlInit($url, $curl_options = array())
    {
        $ch = null;
        if (extension_loaded('curl') && function_exists('curl_init')) {

            if (!($ch = curl_init())) {
                throw new Exception(("Error init curl"));
            }

            if (curl_errno($ch) != 0) {
                throw new Exception(sprintf("Error init curl %d: %s", curl_errno($ch), curl_error($ch)));
            }

            $curl_default_options = array(
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLE_OPERATION_TIMEOUTED => 10,
                CURLOPT_DNS_CACHE_TIMEOUT => 3600,

                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_WRITEFUNCTION => array(__CLASS__, 'curlWriteHandler'),
            );

            if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
                $curl_default_options[CURLOPT_FOLLOWLOCATION] = true;
            }

            foreach ($curl_default_options as $option => $value) {
                if (!isset($curl_options[$option])) {
                    $curl_options[$option] = $value;
                }
            }
            $curl_options[CURLOPT_URL] = $url;
            //TODO read proxy settings from generic config
            $options = array();

            if (!empty($options['host'])) {
                $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
                $curl_options[CURLOPT_PROXY] = sprintf("%s%s", $options['host'], !empty($options['port']) ? ':'.$options['port'] : '');

                if (!empty($options['user'])) {
                    $curl_options[CURLOPT_PROXYUSERPWD] = sprintf("%s:%s", $options['user'], $options['password']);
                }
            }
            foreach ($curl_options as $param => $option) {
                curl_setopt($ch, $param, $option);
            }
        }
        return $ch;
    }

    private static function curlWriteHandler($ch, $chunk)
    {
        $size = 0;
        if (self::$fp && is_resource(self::$fp)) {
            $size = fwrite(self::$fp, $chunk);
            self::$size += $size;
        } else {
            throw new Exception('Invalid write stream');
        }
        return $size;
    }

    public static function upload($url, $path)
    {
        $s = parse_url($url, PHP_URL_SCHEME);
        $w = stream_get_wrappers();
        if (in_array($s, $w) && ini_get('allow_url_fopen')) {
            if ($fp = fopen($url, 'rb')) {
                if (self::$fp = fopen($path, 'wb')) {
                    self::$size = stream_copy_to_stream($fp, self::$fp);
                    fclose(self::$fp);
                } else {
                    fclose($fp);
                    throw new waException('Error while open target file');
                }
                fclose($fp);
            } else {
                throw new waException('Error while open source file');
            }
        } elseif ($ch = self::curlInit($url)) {
            if (self::$fp = fopen($path, 'wb')) {
                self::$size = 0;
                wa()->getStorage()->close();
                curl_exec($ch);
                fclose(self::$fp);
                if ($errno = curl_errno($ch)) {
                    $message = "Curl error: {$errno}# ".curl_error($ch)." at [{$path}]";
                    curl_close($ch);
                    throw new waException($message);
                }
                $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($response_code != 200) {
                    curl_close($ch);
                    throw new waException("Invalid server response with code {$response_code} while request {$url}");
                }
            }
            curl_close($ch);
        } else {
            throw new waException('There no available wrappers');
        }
        return self::$size;
    }

    /**
     * Send file to browser, including appropriate headers.
     *
     * @param string $file - full path to file
     * @param string|boolean $attach - filename to save file with; null to perform default browser action; defaults to null
     * @param boolean $exit - whether to call exit after file has been sent; defaults to true
     * @param boolean $md5 - whether to send Content-MD5 header; defaults to false
     * @throws waException - if file does not exist
     */
    public static function readFile($file, $attach = null, $exit = true, $md5 = false)
    {
        if (file_exists($file)) {
            $response = wa()->getResponse();
            $file_type = self::getMimeType($attach ? $attach : $file);
            if ($md5) {
                $md5 = base64_encode(pack('H*', md5_file($file)));
            }
            @ini_set('async_send', 1);
            $sid = wa()->getStorage()->close();

            if ($attach !== null) {
                $send_as = str_replace('"', '\"', is_string($attach) ? $attach : basename($file));

                //TODO detect nginx internal redirect available
                $allow_accel_redirect = false;

                $file_size = filesize($file);
                if (!$allow_accel_redirect) {
                    $from = $to = false;

                    if ($http_range = waRequest::server('HTTP_RANGE')) {
                        list($dimension, $range) = explode("=", $http_range, 2);
                        $ranges = explode(',', $range);
                        $intervals = array();
                        foreach ($ranges as $range) {
                            $range = trim($range);
                            if (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
                                $intervals[] = array('from' => intval($matches[1]), 'to' => intval($matches[2]));
                            } elseif (preg_match('/^(\d+)-$/', $range, $matches)) {
                                $intervals[] = array('from' => intval($matches[1]), 'to' => $file_size - 1);
                            } elseif (preg_match('/^-(\d+)$/', $range, $matches)) {
                                $intervals[] = array('from' => $file_size - intval($matches[1]), 'to' => $file_size - 1);
                            } else {
                                throw new waException('Requested range not satisfiable', 416);
                            }
                        }
                        foreach ($intervals as $interval) {
                            if ($from === false) {
                                $from = $interval['from'];
                            }
                            if ($to === false) {
                                $to = $interval['to'];
                            } else {
                                if (($to + 1) == $interval['from']) {
                                    $to = $interval['to'];
                                } else {
                                    //hole at interval
                                    throw new waException('Requested range not satisfiable', 416);
                                }
                            }
                        }

                        if ($from < 0 || ($to + 1) > $file_size) {
                            throw new waException('Requested range not satisfiable', 416);
                        }

                        $range_length = $to - $from + 1;
                        $response->setStatus(206);
                        $response->addHeader("Content-Length", $range_length);
                        $response->addHeader("Content-Range", "bytes {$from}-{$to}/{$file_size}");
                    } else {
                        $response->addHeader("Content-Length", $file_size);
                        if ($md5) {
                            $response->addHeader("Content-MD5", $md5);
                        }
                    }

                    $response->addHeader("Cache-Control", "no-cache, must-revalidate");
                    $response->addHeader("Content-type", "{$file_type}");
                    $response->addHeader("Content-Disposition", "attachment; filename=\"{$send_as}\";");
                    $response->addHeader("Last-Modified", filemtime($file));

                    $response->addHeader("Accept-Ranges", "bytes");
                    $response->addHeader("Connection", "close");

                    $fp = fopen($file, 'rb');
                    if ($from) {
                        fseek($fp, $from);
                    }

                    $response->sendHeaders();
                    $response = null;

                    //TODO: adjust chunk size
                    $chunk = 1048576; //1M
                    while (!feof($fp) && $chunk && (connection_status() == 0)) {
                        if ($to) {
                            $chunk = min(1 + $to - @ftell($fp), $chunk);
                        }
                        if ($chunk) {
                            print @fread($fp, $chunk);
                            @flush();
                        }
                    }

                    @fclose($fp);
                } else { //internal nginx redirect
                    $path = substr($file, strlen($nginx_path));
                    $path = preg_replace('@([/\\\\]+)@', '/', '/'.$nginx_base.'/'.$path);

                    $response->addHeader("Content-type", $file_type);
                    $response->addHeader("Content-Disposition", "attachment; filename=\"{$send_as}\";");
                    $response->addHeader("Accept-Ranges", "bytes");
                    $response->addHeader("Content-Length", $file_size);
                    $response->addHeader("Expires", "0");
                    $response->addHeader("Cache-Control", "no-cache, must-revalidate", false);
                    $response->addHeader("Pragma", "public");
                    $response->addHeader("Connection", "close");
                    if ($md5) {
                        $response->addHeader("Content-MD5", $md5);
                    }

                    $response->addHeader("X-Accel-Redirect", $path);
                    //@future
                    //$response->addHeader("X-Accel-Limit-Rate", $rate_limit);
                    }
            } else {
                $response->addHeader("Content-type", $file_type);
                $response->addHeader("Last-Modified", filemtime($file));
                if ($md5) {
                    $response->addHeader("Content-MD5", $md5);
                }
                $response->sendHeaders();
                $response = null;
                @readfile($file);
            }
            if ($exit) {
                if ($response) {
                    $response->sendHeaders();
                }
                exit();
            } elseif ($sid) {
                wa()->getStorage()->open();
            }
        } else {
            throw new waException("File not found", 404);
        }
    }

    /**
     * Protect folder by creating htaccess if it not exists
     *
     * @param string $path
     */
    public static function protect($path)
    {
        self::write($path.'/.htaccess', "Deny from all\n");
    }

    /**
     *
     * Format file size into sting
     * @param int $file_size
     * @param string $format
     * @param string|mixed $dimensions
     */
    public static function formatSize($file_size, $format = '%0.2f', $dimensions = 'Bytes,KBytes,MBytes,GBytes')
    {
        if (!is_array($dimensions)) {
            $dimensions = explode(',', $dimensions);
        }
        $dimensions = array_map('trim', $dimensions);
        $dimension = array_shift($dimensions);
        $_format = '%d';
        while (($file_size > 768) && ($dimension = array_shift($dimensions))) {
            $file_size = $file_size / 1024;
            $_format = $format;
        }
        return sprintf($_format, $file_size).' '._ws($dimension);
    }
}
