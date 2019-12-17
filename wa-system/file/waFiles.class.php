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
     * Creates specified directory, or parent directory for specified file.
     *
     * @param string $path Path to a file or a directory.
     * @param bool $is_dir Flag explicitly denoting that path to a directory is specified.
     *      With false (default choice), method attempts to "guess" whether $path contains a path to a file or a directory.
     * @return string|bool Specified $path value on success, or false on failure.
     */
    public static function create($path, $is_dir = false)
    {
        if (file_exists($path)) {
            return $path;
        }
        $result = $path;
        if (!$is_dir && substr($path, -1) !== '/' && strpos(basename($path), ".") !== false) {
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
     * Copies a file or directory contents.
     *
     * @param string $source_path Path to the original file or directory. If path to a directory is specified, then the
     *     contents of that directory are copied to the specified location. Subdirectories are copied recursively.
     * @param string $target_path Path for saving a copy.
     * @param string|array $skip_pattern Regular expression string describing the format of file and subdirectory names
     *     which must not be copied if a path to a subdirectory is specified in $source_path parameter (otherwise this
     *     regular expression is ignored).
     * @throws Exception
     */
    public static function copy($source_path, $target_path, $skip_pattern = null)
    {
        if (is_dir($source_path)) {
            try {
                if ($dir = opendir($source_path)) {
                    self::create($target_path);
                    while (false !== ($path = readdir($dir))) {
                        if (($path != '.') && ($path != '..')) {
                            $destination = $target_path.'/'.$path;
                            $source = $source_path.'/'.$path;
                            if ($skip_pattern) {
                                foreach ((array)$skip_pattern as $pattern) {
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
                                throw new waException("Not found {$source_path}/{$path}");
                            }
                        }
                    }
                    closedir($dir);
                }
            } catch (Exception $e) {
                if (!empty($dir) && is_resource($dir)) {
                    closedir($dir);
                }
                throw $e;
            }
        } else {
            self::create(dirname($target_path).'/');
            if (@copy($source_path, $target_path)) {
                /*@todo copy file permissions*/
            } else {
                if (file_exists($source_path) && file_exists($target_path) && (filesize($source_path) === 0)) {
                    /*It's ok - it's windows*/
                } else {
                    throw new waException(sprintf(_ws("Error copying file from %s to %s"), $source_path, $target_path));
                }
            }
        }
    }

    /**
     * Moves a file or a directory to specified parent directory.
     * Can be used for renaming.
     *
     * @param string $source_path Path to original file or directory
     * @param string $target_path Path to which the specified file or directory must be copied
     * @return bool Whether moved (renamed) successfully
     */
    public static function move($source_path, $target_path)
    {
        self::create(dirname($target_path));
        return rename($source_path, $target_path);
    }

    /**
     * Writes data to specified file. If file does not exist, it will be created.
     *
     * @param string $path Path for saving a file. An existing file will be overwritten.
     * @param string $content Data to be written to the file.
     * @return int|false
     */
    public static function write($path, $content)
    {
        self::create(dirname($path));
        $h = @fopen($path, "w+");
        if ($h) {
            $length = fwrite($h, $content);
            fclose($h);
        } else {
            $length = false;
        }
        return $length;
    }

    /**
     * Returns array of files and subdirectories in specified directory.
     *
     * @param string $dir Path to directory
     * @param bool $recursive Flag requiring to return the contents of subdirectories. By default (false)
     *     subdirectories' contents are not returned. When true, only the list of files contained in specified parent
     *     directory is returned, without the names of subdirectories.
     * @return array
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
     * Deletes a file or a directory. A directory containing subdirectories is deleted recursively.
     *
     * @param string $path Path to a file or directory.
     * @param boolean $ignore_dir_errors Flag requiring to ignore any errors which may occur during the deletion of
     *     directories. By default (false), errors are not ignored — an exception is thrown.
     * @throws waException
     * @throws Exception
     * @return bool True, if deleted successfully
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
            if (($dir = opendir($path))) {
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

    /**
     * Returns file name extension.
     *
     * @param string $file Path to file or directory
     * @return string
     */
    public static function extension($file)
    {
        if (($i = strrpos($file, '.')) !== false) {
            return strtolower(substr($file, $i + 1));
        }
        return '';
    }

    /**
     * Determines the MIME type of a file by its name extension.
     *
     * @param string $filename File name
     * @return string
     */
    public static function getMimeType($filename)
    {
        $type = self::extension($filename);

        switch ($type) {
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return 'image/jpeg';
            case 'png':
            case 'gif':
            case 'bmp':
            case 'tiff':
                return 'image/'.strtolower($type);
            case 'ico':
                return 'image/x-icon';
            case 'doc':
            case 'docx':
                return 'application/msword';
            case 'xls':
            case 'xlt':
            case 'xlm':
            case 'xld':
            case 'xla':
            case 'xlc':
            case 'xlw':
            case 'xll':
                return 'application/vnd.ms-excel';
            case 'ppt':
            case 'pps':
                return 'application/vnd.ms-powerpoint';
            case 'rtf':
                return 'application/rtf';
            case 'txt':
                return 'text/plain';
            case 'csv':
                return 'text/csv;  charset=utf-8';
            case 'pdf':
                return 'application/pdf';
            case 'html':
            case 'htm':
            case 'php':
                return 'text/html';
            case 'js':
                return 'application/x-javascript';
            case 'json':
                return 'application/json';
            case 'css':
                return 'text/css';
            case 'dtd':
                return 'application/xml-dtd';
            case 'xml':
                return 'application/xml';
            case 'mpeg':
            case 'mpg':
            case 'mpe':
                return 'video/mpeg';
            case 'mp3':
                return 'audio/mpeg3';
            case 'wav':
                return 'audio/wav';
            case 'aiff':
            case 'aif':
                return 'audio/aiff';
            case 'avi':
                return 'video/msvideo';
            case 'wmv':
                return 'video/x-ms-wmv';
            case 'mov':
                return 'video/quicktime';
            case 'zip':
                return 'application/zip';
            case 'tar':
                return 'application/x-tar';
            case 'swf':
                return 'application/x-shockwave-flash';
            case 'eml':
                return 'message/rfc822';

            default:
                return 'application/octet-stream';
        }
    }

    /**
     *
     * Changes text file character encoding.
     *
     * @param string $file Path to file
     * @param string $from Original encoding
     * @param string $to Target encoding
     * @param string $target Optional path to save encoded file to
     * @throws waException
     * @return string Path to file containing converted text
     */
    public static function convert($file, $from, $to = 'UTF-8', $target = null)
    {
        // Open source
        $src = @fopen($file, 'rb');
        if (!$src) {
            return false;
        }

        // Set up stream filter for source
        $filter = sprintf('convert.iconv.%s/%s//IGNORE', $from, $to);
        if (!@stream_filter_prepend($src, $filter)) {
            fclose($src);
            throw new waException("error while register file filter");
        }

        // default destination filename
        if ($target === null) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension) {
                $extension = '.'.$extension;
            }
            $target = preg_replace('@\.[^\.]+$@', '', $file).'_'.$to.$extension;
        }

        // Make sure destination exists for realpath() to work
        if (!file_exists($target)) {
            if (!@touch($target)) {
                fclose($src);
                return false;
            }
        }

        // Make sure we don't overwrite $file if it's the same as $target
        $file = realpath($file);
        $target = realpath($target);
        if (!$target) {
            fclose($src);
            return false;
        }
        $tmp_target = $target;
        if ($target === $file) {
            $tmp_target = tempnam(dirname($target), basename($target));
            $perms = @octdec(substr(decoct(fileperms($file)), 3));
            if ($perms) {
                @chmod($tmp_target, $perms);
            }
        }

        // Open destination
        $dst = @fopen($tmp_target, 'wb');
        if (!$dst) {
            fclose($src);
            return false;
        }

        // Copy from source to destination
        stream_copy_to_stream($src, $dst);
        fclose($src);
        fclose($dst);

        // Move to real destination if $file was the same as $target
        if ($target !== $tmp_target) {
            if (!waFiles::move($tmp_target, $target)) {
                return false;
            }
        }

        return $target;
    }

    private static function curlInit($url, $options)
    {
        $ch = null;
        if (extension_loaded('curl') && function_exists('curl_init')) {

            if (!($ch = curl_init())) {
                throw new waException(("Error init curl"));
            }

            if (curl_errno($ch) != 0) {
                throw new waException(sprintf("Error init curl %d: %s", curl_errno($ch), curl_error($ch)));
            }

            $curl_options = array(
                CURLOPT_HEADER            => 0,
                CURLOPT_RETURNTRANSFER    => 1,
                CURLOPT_TIMEOUT           => 10,
                CURLOPT_CONNECTTIMEOUT    => 10,
                CURLE_OPERATION_TIMEOUTED => 10,
                CURLOPT_DNS_CACHE_TIMEOUT => 3600,
                CURLOPT_BINARYTRANSFER    => true,
                CURLOPT_WRITEFUNCTION     => array(__CLASS__, 'curlWriteHandler'),
            );

            if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
                $curl_options[CURLOPT_FOLLOWLOCATION] = true;
            }

            $curl_options[CURLOPT_URL] = $url;


            $options += self::getDefaultOptions();
            if ($options['verify']) {

                $curl_options[CURLOPT_SSL_VERIFYHOST] = 2;
                $curl_options[CURLOPT_SSL_VERIFYPEER] = true;
                if (is_string($options['verify'])) {
                    if (!file_exists($options['verify'])) {
                        throw new InvalidArgumentException(
                            "SSL CA bundle not found: {$options['verify']}"
                        );
                    }
                    $curl_options[CURLOPT_CAINFO] = $options['verify'];

                }
            } else {
                $curl_options[CURLOPT_SSL_VERIFYHOST] = 0;
                $curl_options[CURLOPT_SSL_VERIFYPEER] = false;
            }

            if (!empty($options['proxy_host'])) {
                $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
                $curl_options[CURLOPT_PROXY] = sprintf(
                    "%s%s",
                    $options['proxy_host'],
                    !empty($options['proxy_port']) ? ':'.$options['proxy_port'] : ''
                );

                if (!empty($options['proxy_user'])) {
                    $curl_options[CURLOPT_PROXYUSERPWD] = sprintf("%s:%s", $options['proxy_user'], $options['proxy_password']);
                }
            }
            foreach ($curl_options as $param => $option) {
                curl_setopt($ch, $param, $option);
            }
        }
        return $ch;
    }

    /**
     * @usedby self::curlInit()
     *
     * @param $ch
     * @param $chunk
     *
     * @return int
     * @throws Exception
     */
    private static function curlWriteHandler($ch, $chunk)
    {
        if (self::$fp && is_resource(self::$fp)) {
            $size = fwrite(self::$fp, $chunk);
            self::$size += $size;
        } else {
            throw new waException('Invalid write stream');
        }
        return $size;
    }

    private static function getDefaultOptions()
    {
        static $options;
        if (empty($options)) {
            $config = wa()->getConfigPath().'/net.php';
            $options = array();
            if (file_exists($config)) {
                $options = include($config);
            }
            if (!is_array($options)) {
                $options = array();
            }

            $options += array(
                'timeout'    => 15,
                'user_agent' => sprintf('Webasyst-Framework/%s', wa()->getVersion('webasyst')),
            );
        }

        return $options;
    }

    private static function getStreamContext($options)
    {
        static $context;
        if (empty($context)) {
            $options += self::getDefaultOptions();
            $context_params = array(
                'ignore_errors' => true,//PHP >= 5.2.10
                'timeout'       => $options['timeout'],
                'user_agent'    => $options['user_agent'],
            );

            $headers = array();
            if (isset($options['proxy_host']) && strlen($options['proxy_host'])) {
                $proxy = $options['proxy_host'];
                if (isset($options['proxy_port']) && intval($options['proxy_port'])) {
                    $proxy .= ':'.intval($options['proxy_port']);
                }
                $context_params['proxy'] = $proxy;

                if (!empty($options['proxy_user'])) {
                    $auth = base64_encode(sprintf('%s:%s', $options['proxy_user'], $options['proxy_password']));
                    $headers[] = "Proxy-Authorization: Basic $auth";
                }
            }
            if ($headers) {
                $context_params['header'] = implode("\r\n", $headers); //5.2.10 array support
            }

            $context_params += array(
                'follow_location' => true,//PHP >= 5.3.4
                'max_redirects'   => 5,
            );

            //SSL
            if (!empty($options['verify'])) {
                $context_params['ssl']['verify_peer'] = true;
                $context_params['ssl']['verify_peer_name'] = true;
                $context_params['ssl']['allow_self_signed'] = false;
                if (is_string($options['verify'])) {
                    if (!file_exists($options['verify'])) {
                        throw new RuntimeException("SSL CA bundle not found: {$options['verify']}");
                    }
                    $context_params['ssl']['cafile'] = $options['verify'];

                } else {
                    // PHP 5.6 or greater will find the system cert by default. When
                    // < 5.6, try load it
                    if (PHP_VERSION_ID < 50600) {
                        //TODO try default system path with ca files
                        //$context_params['ssl']['cafile'] = '';
                    }
                }
            } else {
                $context_params['ssl']['verify_peer'] = false;
                $context_params['ssl']['verify_peer_name'] = false;
            }

            $context = stream_context_create(array('http' => $context_params,));
        }
        return $context;
    }

    /**
     * Uploads a file from specified URL to a server directory.
     *
     * @param string $url  URL from which a file must be retrieved
     * @param string $path Path for saving the downloaded file
     * @param array  $options
     * @return int
     * @throws waException
     */
    public static function upload($url, $path, $options = array())
    {
        $s = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $encoded_host = waIdna::enc($host);
        if ($encoded_host != $host) {
            $url = preg_replace('@^(\w+://)[^/]+/@', '$1'.$encoded_host.'/', $url);
        }
        if (!is_array($options)) {
            $options = array();
        }
        $w = stream_get_wrappers();
        if (in_array($s, $w) && ini_get('allow_url_fopen')) {
            $context = self::getStreamContext($options);
            if ($fp = @fopen($url, 'rb', null, $context)) {
                try {
                    if (self::$fp = @fopen($path, 'wb')) {
                        self::$size = stream_copy_to_stream($fp, self::$fp);
                        fclose(self::$fp);
                        $stream_meta_data = stream_get_meta_data($fp);

                        $headers = array();
                        if (isset($stream_meta_data["wrapper_data"]["headers"])) {
                            $headers = $stream_meta_data["wrapper_data"]["headers"];
                        } elseif (isset($stream_meta_data["wrapper_data"])) {
                            $headers = $stream_meta_data["wrapper_data"];
                        }

                        $header_matches = null;
                        //check server response codes (500/404/403/etc)
                        foreach ($headers as $header) {
                            if (strtolower(substr($header, 0, 10)) == 'location: ') {
                                self::$size = self::upload(substr($header, 10), $path, $options);
                            }
                            if (preg_match('@http/\d+\.\d+\s+(\d+)\s+(.+)$@i', $header, $header_matches)) {
                                $response_code = intval($header_matches[1]);
                                $status_description = trim($header_matches[2]);
                                if ($response_code == 301 || $response_code == 302 ) {
                                    continue;
                                }
                                if ($response_code != 200) {
                                    throw new waException("Invalid server response with code {$response_code} ($status_description) while request {$url}");
                                }
                                break;
                            }
                        }

                    } else {
                        throw new waException('Error while open target file');
                    }
                } catch (waException $ex) {
                    fclose($fp);
                    waFiles::delete($path);
                    throw $ex;
                }
                fclose($fp);
            } else {
                throw new waException("Error while open source file {$url}");
            }
        } elseif ($ch = self::curlInit($url, $options)) {
            if (self::$fp = @fopen($path, 'wb')) {
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
                    waFiles::delete($path);
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
     * Reads file contents and outputs it to browser with appropriate headers.
     *
     * @param string $file File to path
     * @param string|null $attach Name, which will be suggested to user when he requests to download the file.
     *     If not specified, browser's auto-suggestion will be used.
     * @param bool $exit Flag requiring to send file transfer headers to user's browser. By default (true) headers are sent.
     * @param bool $md5 Flag requiring to send the Content-MD5 header. By default (false) this header is not sent.
     * @throws waException If file does not exist
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
            wa()->getStorage()->close();
            $file_size = filesize($file);

            if ($attach !== null) {
                $send_as = str_replace('"', '\"', is_string($attach) ? $attach : basename($file));
                $send_as = preg_replace('~[\n\r]+~', ' ', $send_as);


                /**
                 * @see https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/
                 * @todo fix encoding workaround
                 */
                $x_accel_redirect = waSystemConfig::systemOption('x_accel_redirect');
                if (!empty($x_accel_redirect)) {
                    $base_path = wa()->getConfig()->getRootPath();
                    if (strpos($file, $base_path) !== 0) {
                        $x_accel_redirect = false;
                    }
                } else {
                    $base_path = null;
                }

                $response->setStatus(200);
                if (empty($x_accel_redirect)) {
                    $from = $to = false;
                    $http_range = waRequest::server('HTTP_RANGE');
                    if (!empty($http_range)) {
                        // multi range support incomplete
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
                                $intervals[] = array(
                                    'from' => $file_size - intval($matches[1]),
                                    'to'   => $file_size - 1
                                );
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

                    if (!$response->getHeader("Cache-Control")) {
                        $response->addHeader("Cache-Control", "no-cache, must-revalidate");
                    }
                    $response->addHeader("Content-type", "{$file_type}");
                    $response->addHeader("Content-Disposition", "attachment; filename=\"{$send_as}\"");

                    // wanna has possibility set own value of this header from outside (for example by update_datetime in DB record)
                    if (!$response->getHeader("Last-Modified")) {
                        $response->addHeader("Last-Modified", filemtime($file));
                    }

                    $response->addHeader("Accept-Ranges", "bytes");
                    $response->addHeader("Connection", "close");

                    $fp = fopen($file, 'rb');
                    if ($from) {
                        fseek($fp, $from);
                    }

                    $response->sendHeaders();
                    $response = null;

                    $chunk = 131072; //128KB
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
                } else {

                    $path = wa()->getRootUrl().substr($file, strlen($base_path));
                    $path = preg_replace('@([/\\\\]+)@', '/', $path);

                    $response->addHeader("Content-type", $file_type);
                    //RFC 6266
                    $response->addHeader("Content-Disposition", "attachment; filename=\"{$send_as}\"");
                    $response->addHeader("Accept-Ranges", "bytes");
                    $response->addHeader("Content-Length", $file_size);
                    $response->addHeader("Expires", "0");
                    if (!$response->getHeader("Cache-Control")) {
                        $response->addHeader("Cache-Control", "no-cache, must-revalidate");
                    }
                    $response->addHeader("Pragma", "public");
                    $response->addHeader("Connection", "close");
                    if ($md5) {
                        $response->addHeader("Content-MD5", $md5);
                    }

                    $response->addHeader("X-Accel-Redirect", $path);
                    $response->sendHeaders();
                    $response = null;
                    //@future
                    //$response->addHeader("X-Accel-Limit-Rate", $rate_limit);
                }
            } else {
                $response->addHeader("Content-type", $file_type);
                $response->addHeader("Content-Length", $file_size);
                $response->addHeader("Last-Modified", filemtime($file));
                if ($md5) {
                    $response->addHeader("Content-MD5", $md5);
                }
                $response->sendHeaders();
                $response = null;
                @readfile($file);
            }
            if ($exit) {
                if (!empty($response)) {
                    /**
                     * @var waResponse $response
                     */
                    $response->sendHeaders();
                }
                exit();
            }
        } else {
            throw new waException("File not found", 404);
        }
    }

    /**
     * Protect a directory by creating .htaccess with 'Deny from all', if it does not exist.
     *
     * @param string $path Path to directory
     */
    public static function protect($path)
    {
        self::write($path.'/.htaccess', "Deny from all\n");
    }

    /**
     * Returns formatted file size value.
     *
     * @param int $file_size Numerical file size value.
     * @param string $format Format string for displaying file size value, which must be acceptable for PHP function sprintf().
     * @param string|mixed $dimensions String of comma-separated file size measure units.
     * @return string
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
