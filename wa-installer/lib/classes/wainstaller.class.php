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
 * @package wa-installer
 */

/**
 *
 * @todo symlink workaround
 *
 */
class waInstaller
{
    private static $depended_classes = array(
        'pear'        => 'wa-installer/lib/vendors/PEAR/PEAR.php',
        'archive_tar' => 'wa-installer/lib/vendors/PEAR/Tar.php',
        'durabletar'  => 'wa-installer/lib/transport/durabletar.class.php',
    );

    private static $registered = false;

    const LOG_DEBUG = 1;
    const LOG_TRACE = 2;

    const LOG_WARNING = 3;
    const LOG_ERROR = 4;

    const TIMEOUT_SOCKET = 15;
    const TIMEOUT_RESUME = 60;

    const PATH_LOG = 'wa-log/wa-installer/installer.log';
    const PATH_STATE = 'wa-log/wa-installer/installer.state';
    const PATH_FSTATE = 'wa-log/wa-installer/installer.fstate';
    const PATH_UPDATE = 'wa-data/protected/wa-installer/';
    const CONFIG_PATHS = 'wa-installer/lib/config/path.php';
    const HASH_PATH = '.files.md5';

    const STATE_HEARTBEAT = 'heartbeat';
    const STATE_COMPLETE = 'complete';
    const STATE_RESUME = 'resume';
    const STATE_WAIT = 'wait';
    const STATE_ERROR = 'error';

    const STAGE_FLUSH = 'flush';
    const STAGE_NONE = 'none';
    const STAGE_PREPARE = 'prepare';
    const STAGE_COPY = 'copy';
    const STAGE_DOWNLOAD = 'download';
    const STAGE_EXTRACT = 'extract';
    const STAGE_REPLACE = 'replace';
    const STAGE_CLEANUP = 'cleanup';
    const STAGE_VERIFY = 'verify';
    const STAGE_UPDATE = 'update';

    private $log_level;
    private $log_handler;
    private $env;

    private static $root_path;
    private static $update_path;
    private $thread_id;
    private $current_stage;
    private $current_chunk_id;

    private $stage_data_stack = array();

    private static $ob_skip = true;

    /**
     * waInstaller constructor.
     * @param int  $log_level
     * @param null $thread_id
     * @throws Exception
     */
    public function __construct($log_level = self::LOG_WARNING, $thread_id = null)
    {
        $this->log_level = max(self::LOG_DEBUG, min(self::LOG_ERROR, $log_level));
        if (!isset(self::$root_path)) {
            self::$root_path = self::formatPath(dirname(__FILE__)).'/';
            self::$root_path = preg_replace('@(/)wa-installer/lib/classes/?$@', '$1', self::$root_path);
        }
        if (!isset(self::$update_path)) {
            self::$update_path = self::PATH_UPDATE;
            if (file_exists(self::$root_path.self::CONFIG_PATHS)) {
                $paths = include(self::$root_path.self::CONFIG_PATHS);
                if (is_array($paths) && isset($paths['update_path']) && $paths['update_path']) {
                    self::$update_path = $paths['update_path'];
                }
            }

        }
        $this->thread_id = $thread_id ? $thread_id : self::makeThread();

        if (!self::$registered) {
            @ini_set('unserialize_callback_func', 'spl_autoload_call');
            @ini_set('include_path', './');
            $method = array(__CLASS__, 'autoload');
            if (false === spl_autoload_register($method)) {
                throw new Exception(sprintf('Unable to register %s::%s  as an autoloading method.', $method[0], $method[1]));
            } else {
                self::$registered = true;
            }
        }
    }

    public static function makeThread()
    {
        return strtoupper(dechex(time() % 4096));
    }

    public function getLogPath()
    {
        return self::$root_path.self::PATH_LOG;
    }

    /**
     *
     * Init update progress
     * @todo resume workaround
     */
    public function init()
    {
        $this->setState();
    }

    /**
     *
     * @todo root path workaround
     * @todo partial copy
     *
     * @throws Exception|waInstallerDownloadException
     * @param $update_list array[][string]string
     * @param $update_list []['source'] array[][string]string Source path or URI
     * @param $update_list []['target'] array[][string]string Target path
     * @param $update_list []['slug'] array[][string]string Update item slug (optional item identity)
     * @param $update_list []['md5'] array[][string]string MD5 of source archive (optional)
     *
     * @return array[][string]mixed
     * @return array[]['skipped']boolean
     * @return array[]['source']mixed
     * @return array[]['source']mixed
     * @return array[]['source']mixed
     * @return array[]['source']mixed
     * @return array[]['source']mixed
     * @return array[]['source']mixed
     */
    public function update($update_list)
    {
        $sourcesFile = waInstallerApps::CONFIG_SOURCES;
        $update_path = self::$update_path.$this->thread_id.'/';
        $download_path = $update_path.'download/';
        try {
            $this->formatUpdateList($update_list, $update_path);
            $this->envSet();

            $state = $this->getState();
            $resume = isset($state['stage']) ? $state['stage'] : false;

            self::$ob_skip = false;

            //TODO write statistics into stage operations

            //TODO allow skip some update parts

            $sourceInfo = $this->fileInfo($sourcesFile);

            switch ($resume) {
                case false:
                    //no break
                case self::STAGE_FLUSH:
                    //no break
                case self::STAGE_NONE:
                    $this->setFullState(null);
                    //no break
                case self::STAGE_PREPARE:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['dependent']) {
                            $update['current_size'] = false;
                        } else {
                            $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                            /** @uses waInstaller::stagePrepare() */
                            $update['current_size'] = $this->run(self::STAGE_PREPARE, $update['pass'], $download_path, $update['extract_path'], $update['target']);
                        }
                    }
                    unset($update);
                    //no break
                case self::STAGE_COPY:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped']) {
                            continue;
                        }
                        if (file_exists(self::$root_path.$update['target']) && !$update['dependent']) {
                            $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                            /** @uses waInstaller::stageCopy() */
                            $result = $this->run(self::STAGE_COPY, $update['pass'], $update['target'], $update['extract_path'], $update['current_size']);
                            if (!$result && $update['pass']) {
                                $update['skipped'] = true;
                            }
                        }
                    }
                    unset($update);
                    //no break
                case self::STAGE_DOWNLOAD:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped']) {
                            continue;
                        }
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        //TODO check name for $download_path
                        /** @uses waInstaller::stageDownload() */
                        $update['archive'] = $this->run(self::STAGE_DOWNLOAD, $update['pass'], $update['source'], $download_path, isset($update['md5']) ? $update['md5'] : false);
                        if (!$update['archive'] && $update['pass']) {
                            $update['skipped'] = true;
                        }
                    }
                    unset($update);
                    //no break
                case self::STAGE_EXTRACT:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped']) {
                            continue;
                        }
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        /** @uses waInstaller::stageExtract() */
                        $result = $this->run(self::STAGE_EXTRACT, $update['pass'], $update['archive'], $update['extract_path'], $update['target']);
                        if (!$result && $update['pass']) {
                            $update['skipped'] = true;
                        }
                    }
                    unset($update);
                    //no break
                case self::STAGE_REPLACE:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped'] || $update['dependent']) {
                            continue;
                        }
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        /** @uses waInstaller::stageReplace() */
                        $update['backup'] = $this->run(self::STAGE_REPLACE, $update['pass'], $update['extract_path'], $update['target']);
                        if (!$update['backup'] && $update['pass']) {
                            $update['skipped'] = true;
                        }
                    }
                    unset($update);
                    //no break
                case self::STAGE_CLEANUP:
                    foreach ($update_list as $chunk_id => & $update) {
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        $paths = array();
                        $paths[$update_path] = true;
                        $dir = ($this->current_chunk_id == 'wa-system' ? 'webasyst' : $this->current_chunk_id);
                        $dir = str_replace('/widgets/', '_widgets/', $dir);
                        $dir = preg_replace('@^/?wa-apps/@', '', $dir);
                        $dir = str_replace('@/plugins/@', '_', $dir);
                        $cache_path = 'wa-cache/apps/'.$dir;
                        $paths[$cache_path] = true;
                        if (class_exists('waConfig') && class_exists('waSystem')) {
                            $cache_path = waConfig::get('wa_path_cache').'/apps/'.$dir;
                            $cache_path = substr($cache_path, strlen(waSystem::getInstance()->getConfig()->getRootPath()) + 1);
                            $paths[$cache_path] = true;
                        }
                        /** @uses waInstaller::stageCleanup() */
                        $this->run(self::STAGE_CLEANUP, false, $paths);
                    }
                    unset($update);
                    //no break
                case self::STAGE_VERIFY:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped']) {
                            continue;
                        }
                        if (!isset($update['verify'])) {
                            continue;
                        }
                        if (strpos($update['target'], 'wa-config') !== false) {
                            $update['verify'] = false;
                        }
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        /** @uses waInstaller::stageVerify() */
                        $this->run(self::STAGE_VERIFY, false, $update['target'], isset($update['verify']) && $update['verify']);

                    }
                    unset($update);
                    //no break
                case self::STAGE_UPDATE:
                    foreach ($update_list as $chunk_id => & $update) {
                        if ($update['skipped']) {
                            continue;
                        }
                        $this->current_chunk_id = isset($update['slug']) ? $update['slug'] : $chunk_id;
                        /** @uses waInstaller::stageUpdate() */
                        $this->run(self::STAGE_UPDATE, false);
                    }
                    unset($update);
                    break;
                default:
                    throw new Exception("Invalid resume state {$resume}");
                    break;
            }
            //$this->current_stage = 'update_'.self::STATE_COMPLETE;
            //$this->current_chunk_id = 'total';
            //$this->setState();
            $this->writeLog(__METHOD__, self::LOG_DEBUG, compact('update_list'));
            self::$ob_skip = true;
            $this->envReset();

            /** #51.6072 */
            if ($sourceInfo !== $this->fileInfo($sourcesFile)) {
                if (class_exists('waLog')) {
                    waLog::log(__METHOD__.' '.$sourceInfo.' ->> '.$this->fileInfo($sourcesFile), 'file_sources.log');
                }
            }

            return $update_list;
        } catch (Exception $ex) {
            $this->cleanupPath($update_path, true);
            $this->writeLog($ex->getMessage(), self::LOG_WARNING, compact('update_list'));
            $this->envReset();
            self::$ob_skip = true;
            throw $ex;
        }
    }

    /**
     *
     * @todo complete code
     */
    public function verify()
    {

    }

    /**
     * @throws Exception
     */
    public function flush()
    {
        try {
            //reset state
            $this->current_stage = __FUNCTION__.'_'.self::STATE_HEARTBEAT;
            $this->setState();
            $this->cleanupPath(self::$update_path);
            $this->current_stage = __FUNCTION__.'_'.self::STATE_COMPLETE;
            $this->setState();
        } catch (Exception $ex) {
            $this->writeLog($ex->getMessage());
            throw $ex;
        }
    }

    private static function sortUpdateList($a, $b)
    {
        if ($a['pass'] == $b['pass']) {
            if (preg_match('@wa-apps\/@', $a['target'])) {
                if (preg_match('@wa-apps\/@', $b['target'])) {
                    //TODO dependent workaround
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 0;
            }
        }
        return ($a['pass'] < $b['pass']) ? -1 : 1;
    }

    private function envSet()
    {
        $this->env = array();
        $this->env['session_id'] = session_id();
        if ($this->env['session_id']) {
            if (function_exists('wa') && method_exists($wa = wa(), 'getStorage')) {
                $wa->getStorage()->close();
            } else {
                session_write_close();
            }
        }

        if ($this->log_level >= self::LOG_DEBUG) {
            $this->writeLog('callback', self::LOG_DEBUG, self::debugBacktraceCustom());
        }

        $this->env['error_level'] = error_reporting();
        $this->env['display_errors'] = ini_get('display_errors');
        $this->env['error_reporting'] = ini_get('error_reporting');
        @ini_set('display_errors', true);
        @ini_set('error_reporting', E_ALL & ~E_NOTICE);
        error_reporting(E_ALL & ~E_NOTICE);
        ignore_user_abort(true);
        $this->writeLog('Register ob error handler', self::LOG_TRACE, ob_start(__CLASS__.'::obHandler'));
    }

    private function envReset()
    {
        error_reporting($this->env['error_level']);
        @ini_set('display_errors', $this->env['display_errors']);
        @ini_set('error_reporting', $this->env['error_reporting']);
        if ($this->env['session_id']) {
            if (function_exists('wa') && method_exists($wa = wa(), 'getStorage')) {
                $wa->getStorage()->open();
            } else {
                session_start();
            }
        }
    }

    private function formatUpdateList(&$update_list, $update_path)
    {
        $targets = array();
        foreach ($update_list as & $update) {
            $update['target'] = self::formatPath($update['target']).'/';
            $update['target'] = preg_replace('@(^|/)\.\./@', '/', $update['target']);
            $update['extract_path'] = $update_path.'update/'.$update['target'];
            if (!isset($update['pass'])) {
                $update['pass'] = false;
            }
            if (!isset($update['skipped'])) {
                $update['skipped'] = false;
            }

            $founded = false;
            foreach ($targets as $id => $target) {
                if (strpos($target, $update['target']) === 0) {
                    $founded = true;
                    if (strlen($target) > strlen($update['target'])) {
                        $targets[$id] = $update['target'];
                    }
                    break;

                } elseif (strpos($update['target'], $target) === 0) {
                    $founded = true;
                    break;
                }

            }
            if (!$founded) {
                $targets[] = $update['target'];
            }
            unset($update);
        }

        foreach ($update_list as & $update) {
            $update['dependent'] = false;
            foreach ($targets as $target) {
                if (strpos($update['target'], $target) === 0) {
                    if (strlen($target) < strlen($update['target'])) {
                        $update['dependent'] = true;
                    }
                    break;
                }

            }
            unset($update);
        }

        $this->writeLog(__METHOD__.' tree', self::LOG_DEBUG, array(
            'targets'     => $targets,
            'update_list' => $update_list,
        ));

        #sort
        uasort($update_list, array(__CLASS__, 'sortUpdateList'));
        return $targets;
    }

    /**
     *
     * @param $action
     * @param $pass
     * @throws Exception
     * @return mixed
     */
    private function run($action, $pass)
    {
        static $allowed_methods = array();
        if (!$allowed_methods) {
            $allowed_methods = get_class_methods(__CLASS__);
        }
        $args = func_get_args();
        $args = array_slice($args, 2);
        try {
            $method_name = 'stage'.ucfirst($action);
            if (!in_array($method_name, $allowed_methods)) {
                throw new Exception("Not allowed stage {$action}");
            }
            $this->current_stage = $action.'_'.self::STATE_HEARTBEAT;
            $this->setState();
            $result = call_user_func_array(array(&$this, $method_name), $args);
            $this->current_stage = $action.'_'.self::STATE_COMPLETE;
            $this->setState();
        } catch (Exception $ex) {
            $this->writeLog(__METHOD__.' args', self::LOG_DEBUG, $args);
            $this->current_stage = $action.'_'.self::STATE_ERROR;
            $this->setState(array('error' => $ex->getMessage()));
            if ($pass) {
                $result = false;
            } else {
                throw $ex;
            }
        }
        return $result;
    }

    /**
     * @param $method
     * @param $args
     * @throws Exception
     */
    public function __call($method, $args)
    {
        /**
         * @todo pass throw internal methods like setState and adjustStageChunk at callback
         */
        throw new Exception("Couldn't exec method {$method} of class ".__CLASS__);
    }

    /**
     * Prepare paths for update
     * @param $download_path
     * @param $extract_path
     * @param $target_path
     * @return int
     * @throws Exception
     * @todo     optional check file changes
     */
    private function stagePrepare($download_path, $extract_path, $target_path)
    {
        if (file_exists(self::$root_path.$extract_path)) {
            $this->cleanupPath($extract_path);
        } else {
            $this->mkdir($extract_path);
        }
        $this->cleanupPath($download_path);

        $target_path = self::formatPath($target_path);
        $size = 0;
        if (file_exists(self::$root_path.$target_path)) {
            $size = $this->checkRequiredSpace($target_path);
            /*
             $stop_paths = array('/.svn','/.git',$target_path.'/.svn/',$target_path.'/.git/');
             foreach($stop_paths as $stop_path) {
             if(file_exists(self::$root_path.$stop_path)) {
             throw new Exception("Update at developer instance are disabled\n (<b>{$stop_path}</b> founded)");
             }
             }
             */
        }
        return $size;
    }

    /**
     * @todo extract download code into separate class and add more wrappers
     * (http,https,ftp,svn and other sources) support as primary or plugins
     * @throws Exception
     * @param $source_file
     * @param $temporary_path
     * @param $md5
     * @return string downloaded file_path
     */
    private function stageDownload($source_file, $temporary_path, $md5 = null)
    {
        $header_md5 = null;
        $target_file = null;
        $real_content_length = null;
        try {
            if (preg_match('@^https?://@', $source_file) && $this->curlAvailable()) {
                try {
                    list($target_file, $content_length, $header_md5) = $this->downloadCurl($source_file, $temporary_path);
                } catch (Exception $ex) {
                    $this->writeLog($ex->getMessage(), self::LOG_ERROR);
                    //attempt to download via standard wrapper
                    list($target_file, $content_length, $header_md5) = $this->downloadStandard($source_file, $temporary_path);
                }
            } else {
                list($target_file, $content_length, $header_md5) = $this->downloadStandard($source_file, $temporary_path);
            }

            //TODO check target file size (and retry to download it if incomplete)
            if (!empty($content_length) && ($real_content_length = filesize($target_file)) && ($content_length != $real_content_length)) {
                throw new Exception(sprintf(_w("Invalid file size. Expected %d but get %d"), $content_length, $real_content_length));
            }

            //check content
            if ($real_content_length && ($real_content_length < 1024) && ($decoded = base64_decode(file_get_contents($target_file), true))) {

                $message = unserialize($decoded);
                $this->writeLog('Invalid server response while file download', self::LOG_ERROR, $message);
                throw new Exception(_w('Invalid server response while file download'));
            }
            if ($header_md5 && ($header_md5 != $md5)) {
                if ($md5) {
                    $this->writeLog(sprintf(_w('MD5 hash are changed from %s to %s'), $md5, $header_md5), self::LOG_WARNING);
                } else {
                    $this->writeLog(sprintf(_w('MD5 hash %s get from header'), $header_md5), self::LOG_TRACE);
                }
                $md5 = $header_md5;
            }

            //check MD5 file hash
            if ($md5 && ($real_md5 = md5_file($target_file)) && (strcasecmp($md5, $real_md5) != 0)) {
                throw new Exception(sprintf(_w("Invalid file md5 hash. Expected %s but get %s"), $md5, $real_md5));
            } elseif (empty($md5)) {
                $this->writeLog(sprintf(_w('MD5 hash missing for file %s'), $source_file), self::LOG_WARNING);
            }
            return $target_file;
        } catch (Exception $ex) {
            //write state and error message
            if ($target_file && ($target_file != $source_file) && file_exists($target_file)) {
                @unlink($target_file);
            }
            throw $ex;
        }
    }

    /**
     * @param $source
     * @param $temporary_path
     * @return array
     * @throws Exception|waInstallerDownloadException
     */
    private function downloadStandard($source, $temporary_path)
    {
        $source_stream = null;
        $target_stream = null;
        $md5 = null;
        try {
            $this->writeLog(__METHOD__.' :download via fopen', self::LOG_TRACE);
            /**
             * @var integer describe download file size
             */
            $content_length = 0;
            $target = null;
            //TODO calculate estimated time / speed
            //TODO allow resume downloading
            $name = md5(preg_replace('/(\?.*$)/', '', $source));

            $default_socket_timeout = @ini_set('default_socket_timeout', self::TIMEOUT_SOCKET);
            //TODO use file_exists for local sources
            $source_stream = @fopen($source, 'r');
            @ini_set('default_socket_timeout', $default_socket_timeout);
            if (!$source_stream) {
                $hint = 'for details see update log;';
                if (preg_match('@^([a-z\.]+)://@', $source, $matches)) {
                    $wrappers = stream_get_wrappers();
                    if (!in_array($matches[1], $wrappers)) {
                        $hint .= " Stream {$matches[1]} not supported;";
                    }
                }
                if (preg_match('@^https?://@', $source) && !ini_get('allow_url_fopen')) {
                    $hint .= " PHP ini option 'allow_url_fopen' are disabled;";
                }

                // Extract http status (and form hint)
                $status = 500;
                if (!empty($http_response_header)) {;
                    foreach ($http_response_header as $header) {
                        if (preg_match( "#http/[0-9\.]+\s+([0-9]+)\s+(.+)#i", $header, $matches)) {
                            $status = intval($matches[1]);  // typecast here is important, so === would work in outside (consumer) codes
                            $hint .= " {$matches[1]} {$matches[2]}.";
                            $hint .= self::getHintByStatus($matches[1]);
                        }

                    }
                }

                $source = preg_replace('@([\?&](previous_hash|hash|token)=)([^&\?]+)@', '$1*hash*', $source);
                throw new waInstallerDownloadException("Error while opening source stream [{$source}]. Hint: {$hint}", $status);
            } elseif (!empty($http_response_header)) {
                //XXX ????
                foreach ($http_response_header as $header) {
                    $this->writeLog(__METHOD__, self::LOG_DEBUG, $header);
                    if (preg_match('@^X-license:\s+(\w+)$@i', $header, $matches)) {
                        waInstallerApps::setGenericOptions(array('license_key' => $matches[1]));
                    } elseif (preg_match('@^Content-MD5:\s+(.+)$@i', $header, $matches)) {
                        if (preg_match('@^[0-9A-F]{32}$@', $matches[1])) {
                            $md5 = strtolower($matches[1]);
                        } elseif ($matches = unpack('H*', base64_decode($matches[1]))) {
                            if (preg_match('@^[0-9A-F]{32}$@i', $matches[1])) {
                                $md5 = strtolower($matches[1]);
                            }
                        }
                    }
                }
            }

            $this->setState();

            if (stream_is_local($source_stream)) {
                fclose($source_stream);
                $target = $source;
                $this->writeLog(__METHOD__.' :Source file is local', self::LOG_TRACE, $target);
            } else {
                //TODO check target path rights
                $target = self::formatPath(self::$root_path.$temporary_path.'/'.$name.'');
                $this->mkdir($temporary_path);
                $target_stream = @fopen($target, 'wb');
                if (!$target_stream) {
                    throw new Exception("Error while write temporal download file {$target}");
                }
                $this->writeLog(__METHOD__.' :Source file is distant', self::LOG_TRACE, array('source' => $source, 'target' => $target));

                //{{Read source properties
                list($content_length, $download_content_length, $buf) = $this->getStreamInfo($source_stream);

                //}}Read source properties
                $this->setState(array('stage_value' => $content_length, 'stage_current_value' => $download_content_length));
                if ($buf) {
                    fwrite($target_stream, $buf);
                }

                $download_chunk_size = max($content_length ? ceil($content_length / 10240000) * 102400 : 102400, 102400);
                $retry_counter = 0;
                while (($delta = stream_copy_to_stream($source_stream, $target_stream, $download_chunk_size))
                    ||
                    ($content_length && ($download_content_length < $content_length) && (++$retry_counter < 20))
                    ||
                    (!$content_length && (++$retry_counter < 3))) {
                    if ($delta) {
                        $download_content_length += $delta;
                        if ($retry_counter) {
                            $this->writeLog(
                                __METHOD__.' complete server data transfer',
                                self::LOG_TRACE,
                                compact('content_length', 'download_content_length', 'retry_counter', 'delta')
                            );
                            $retry_counter = 0;
                        }
                    } else {
                        $this->writeLog(__METHOD__.' wait server data transfer', self::LOG_TRACE, compact('content_length', 'download_content_length', 'retry_counter', 'delta'));
                        sleep(3);
                    }
                    $performance = $this->setState(array('stage_current_value' => $download_content_length, 'debug' => $download_chunk_size));
                    //adjust download chunk size
                    //MAX = 8Mb/s MIN = 100Kb/s step 10Kb
                    $download_chunk_size = $this->adjustStageChunk($download_chunk_size, $performance, __FUNCTION__, 10240, 102400, 8388608);
                }

                fclose($source_stream);
                fclose($target_stream);
            }
            return array($target, $content_length, $md5);
        } catch (Exception $ex) {
            //write state and error message
            if ($source_stream && is_resource($source_stream)) {
                fclose($source_stream);
            }
            if ($target_stream && is_resource($target_stream)) {
                fclose($target_stream);
            }
            throw $ex;
        }
    }

    /**
     * @param $source
     * @param $temporary_path
     * @return array
     * @throws Exception
     */
    private function downloadCurl($source, $temporary_path)
    {
        $target_stream = null;
        try {
            $name = md5(preg_replace('/(\?.*)$/', '', $source));
            $target = self::formatPath(self::$root_path.$temporary_path.'/'.$name);
            $this->writeLog(__METHOD__.' :download via cURL', self::LOG_TRACE, compact('source', 'target'));
            $this->mkdir($temporary_path);
            $target_stream = @fopen($target, 'wb');
            if (!$target_stream) {
                throw new Exception("Error while write temporal download file {$target}");
            }

            $content_length = 0;
            $download_content_length = 0;
            $header_md5 = null;
            $ch = $this->getCurl($source);

            $this->stage_data_stack = array(
                'stream'              => & $target_stream,
                'stage_value'         => & $content_length,
                'stage_current_value' => & $download_content_length,
                'stream_md5'          => & $header_md5,
            );

            curl_exec($ch);
            if ($errno = curl_errno($ch)) {
                $message = "Curl error: {$errno}# ".curl_error($ch)." at [{$source}]";
                throw new Exception($message);
            }

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                throw new Exception("Invalid server response with code {$status}");
            }
            curl_close($ch);
            fclose($target_stream);
            //list($content_length,$download_content_length,$buf) = $this->getStreamInfo($source_stream);

            $this->setState(array('stage_value' => $content_length, 'stage_current_value' => $download_content_length));

            return array($target, $content_length, $header_md5);
        } catch (Exception $ex) {
            if (!empty($ch)) {
                curl_close($ch);
            }
            if ($target_stream && is_resource($target_stream)) {
                fclose($target_stream);
            }

            throw $ex;
        }
    }

    private static function getHintByStatus($status)
    {
        $hint = '';
        switch ($status) {
            case 402:
                $hint = " Check your order status;";
                break;
            case 502:
                $hint = " Please try later;";
                break;
        }
        return $hint;
    }

    /**
     * @param $ch
     * @param $chunk
     * @return bool|int
     * @throws Exception
     */
    public function curlWriteHandler($ch, $chunk)
    {

        if (!$this->stage_data_stack['stream'] || !is_resource($this->stage_data_stack['stream'])) {
            throw new Exception('Invalid write stream');
        }
        $size = fwrite($this->stage_data_stack['stream'], $chunk);
        $this->stage_data_stack['stage_current_value'] += $size;
        $this->writeLog(__METHOD__, self::LOG_DEBUG, curl_getinfo($ch));
        $state_data = array(
            'stage_value'         => $this->stage_data_stack['stage_value'],
            'stage_current_value' => $this->stage_data_stack['stage_current_value'],
        );
        $this->setState($state_data);
        return $size;
    }

    /**
     * @param $ch object
     * @param $header
     * @return int
     * @throws Exception
     */
    public function curlHeaderHandler($ch, $header)
    {
        $header_matches = null;
        $field = false;
        if (preg_match('@content-length:\s*\b(\d+)\b@i', $header, $header_matches)) {
            $this->stage_data_stack['stage_value'] = intval($header_matches[1]);
            $field = 'stage_value';
        } elseif (preg_match('@^content-md5:\s+(.+)$@i', $header, $header_matches)) {
            if (preg_match('@^[0-9A-F]{32}$@', $header_matches[1])) {
                $this->stage_data_stack['stream_md5'] = strtolower($header_matches[1]);
                $field = 'stream_md5';
            } elseif ($header_matches = unpack('H*', base64_decode($header_matches[1]))) {
                if (preg_match('@^[0-9A-F]{32}$@i', $header_matches[1])) {
                    $this->stage_data_stack['stream_md5'] = strtolower($header_matches[1]);
                    $field = 'stream_md5';
                }
            }
        }
        if ($field) {
            $value = $this->stage_data_stack[$field];
            $this->writeLog(__METHOD__, self::LOG_DEBUG, compact('header', 'header_matches', 'value'));
        }
        return strlen($header);
    }

    /**
     * @todo add more types support (tar.gz - new or backup, svn and etc)
     *
     * @param $compressed_file
     * @param $target_path
     * @param string $base_path
     * @throws Exception
     * @return boolean
     */
    private function stageExtract($compressed_file, $target_path, $base_path = '')
    {
        $this->writeLog(__METHOD__, self::LOG_TRACE, compact('compressed_file', 'target_path', 'base_path'));

        //TODO test open archive
        //TODO check write permissions/file contents

        //extract files
        if (!class_exists('DurableTar')) {
            throw new Exception('class DurableTar not found');
        }
        $resumeOffset = null;
        $tarSize = null;
        $tar = new DurableTar($compressed_file);
        $tar->setResume($resumeOffset, $tarSize);
        $tar->setErrorHandling(PEAR_ERROR_PRINT);
        $tar->setStateHandler(array(&$this, 'setState'));
        $tar->setPerformanceHandler(array(&$this, 'adjustStageChunk'));
        ob_start();
        if (class_exists('waTheme')) {
            $base_path = str_replace(waTheme::getTrialUrl(), 'wa-apps/', $base_path);
        }
        $result = $tar->extractModify(self::$root_path.$target_path, $base_path);
        $tar_out = ob_get_clean();

        $locale_path = rtrim(self::$root_path.$target_path, '/').'/locale';
        // Mark localization files as recently changed.
        // This forces use of PHP localization adapter that does not get stuck in apache cache.
        if (file_exists($locale_path)) {
            $all_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($locale_path));
            $po_files = new RegexIterator($all_files, '~(\.po)$~i');
            foreach ($po_files as $f) {
                @touch($f->getPathname());
            }
        }

        if (!$result) {
            throw new Exception("Error while extracting {$compressed_file}: {$tar_out}");
        } elseif ($tar_out) {
            $this->writeLog("Message while extracting {$compressed_file}: {$tar_out}", self::LOG_WARNING);
        }
        return $result;
    }

    /**
     * Get path size
     * @param string $source_path
     * @throws Exception
     * @return int
     */
    private function checkRequiredSpace($source_path)
    {
        $source_size = 0;
        $source_path = self::formatPath($source_path);
        $this->getSpaceUsage($source_path, $source_size);
        if (file_exists(self::$root_path.$source_path) && !is_writable(self::$root_path.$source_path)) {
            throw new Exception(_w("Invalid file permissions").' '.$source_path);
        }
        if (function_exists('disk_free_space') && is_callable('disk_free_space')) {
            $disk_free_space = disk_free_space(self::$root_path);
            $this->writeLog(__FUNCTION__, self::LOG_TRACE, compact('disk_free_space', 'source_size'));
            if ($disk_free_space && ($source_size > $disk_free_space)) {
                throw new Exception("Not enough disk space. Required at least {$source_size} but get {$disk_free_space}");
            }
        }
        return $source_size;
    }

    /**
     * Copy current version into temp path
     * @param $source_path
     * @param $target_path
     * @param int $source_size
     * @param int $mode
     * @param int $level
     * @throws Exception
     * @return boolean
     */
    private function stageCopy($source_path, $target_path, $source_size = 0, $mode = 0777, $level = 0)
    {
        $target_path = self::formatPath($target_path);
        $source_path = self::formatPath($source_path);
        static $copied_size = 0;
        static $last_copied_size = 0;
        static $chunk_size = 1048576;
        $this->writeLog(__METHOD__, self::LOG_TRACE, compact('source_path', 'target_path', 'source_size'));
        if (!$level) {
            $copied_size = 0;
            $last_copied_size = 0;
            $this->setState(array('stage_value' => $source_size, 'stage_current_value' => 0));
        }
        if (!$source_size) {
            return true;
        }
        if (file_exists(self::$root_path.$source_path) && is_dir(self::$root_path.$source_path)) {
            if (!file_exists(self::$root_path.$target_path)) {
                $this->mkdir($target_path, $mode);
            } elseif (!is_dir(self::$root_path.$target_path)) {
                throw new Exception("Error on make dir {$target_path} - it's a file");

            } elseif (!is_writable(self::$root_path.$target_path)) {
                throw new Exception("Error on access {$target_path} write forbidden");
            }
            try {

                if ($dir = opendir(self::$root_path.$source_path)) {
                    while (false !== ($path = readdir($dir))) {
                        if (($path != '.') && ($path != '..')) {
                            $destiny = self::$root_path.$target_path.'/'.$path;
                            $source = self::$root_path.$source_path.'/'.$path;
                            if (file_exists($source)) {
                                if (is_dir($source)) {
                                    if (!$this->skipPath($path)) {
                                        $this->stageCopy($source_path.'/'.$path, $target_path.'/'.$path, $source_size, $mode, $level + 1);
                                    }
                                } elseif (is_link($source)) {
                                    //TODO copy symlink for new path
                                } else {
                                    if (!file_exists($destiny)) { //skip file move on resume
                                        if (@copy($source, $destiny)) {
                                            if (false) {
                                                $file_mode = fileperms($source);
                                                if ($file_mode !== false) {
                                                    chmod($destiny, $file_mode);
                                                }
                                            }
                                        } else {
                                            if (file_exists($destiny) && (filesize($source) === 0)) {
                                                //It's ok - it's windows
                                            } else {
                                                throw new Exception("error on copy from {$source_path}/{$path} to {$target_path}/{$path}");
                                            }
                                        }
                                    }

                                    $copied_size += filesize($destiny);
                                    if (($copied_size - $last_copied_size) > $chunk_size) {
                                        $last_copied_size = $copied_size;
                                        //adjust copy chunk size
                                        $performance = $this->setState([
                                            'stage_current_value' => $copied_size,
                                            'stage_value'         => $source_size,
                                            'debug'               => $chunk_size
                                        ]);
                                        $chunk_size = $this->adjustStageChunk($chunk_size, $performance, __FUNCTION__, 16384, 16384, 4194304);
                                    }
                                }
                            } else {
                                throw new Exception("Not found {$source_path}/{$path}");
                            }
                        }
                    }
                    closedir($dir);
                } else {
                    throw new Exception("Error during open {$source_path}");
                }
                return true;
            } catch (Exception $ex) {
                if (!empty($dir) && is_resource($dir)) {
                    closedir($dir);
                }
                throw $ex;
            }
        }
        return false;
    }

    /**
     * @param string $path
     * @param int    $size
     * @throws Exception
     */
    private function getSpaceUsage($path, &$size)
    {
        static $size_heartbeat = 0;
        try {
            $full_path = self::$root_path.$path;
            if (file_exists($full_path)) {
                if (is_dir($full_path)) {
                    if ($dir = opendir($full_path)) {
                        while (false !== ($path_name = readdir($dir))) {
                            if (($path_name != '.') && ($path_name != '..')) {
                                $file_path = $path.'/'.$path_name;
                                $full_path = self::$root_path.$file_path;
                                if (file_exists($full_path)) {
                                    if (is_dir($full_path)) {
                                        if ($path_name == '.svn') {
                                            //throw new Exception("Update at developer instance are disabled\n (<b>{$file_path}</b> founded)");
                                        }
                                        if ($this->skipPath($path_name)) {

                                        } else {
                                            $this->getSpaceUsage($path.'/'.$path_name, $size);
                                        }
                                    } elseif (is_link($full_path)) {
                                        //skip symlinks
                                    } else {
                                        $size += filesize($full_path);
                                    }
                                } else {
                                    throw new Exception("Not found {$path}/{$path_name}");
                                }
                                if (($size - $size_heartbeat) > 8388608) {
                                    $this->setState(array('debug' => $size));
                                    $size_heartbeat = $size;
                                }
                            }
                        }
                        closedir($dir);
                    } else {
                        throw new Exception("Error during open {$full_path}");
                    }
                } elseif (is_link($full_path)) {
                    //skip symlinks
                } else {
                    $size += filesize($full_path);
                }
            }
        } catch (Exception $ex) {
            if (!empty($dir) && is_resource($dir)) {
                closedir($dir);
            }
            throw $ex;
        }
    }

    /**
     * Replace current version by merged old and updated files
     * @param $source_path string
     * @param $target_path string
     * @param $store_prev boolean
     * @throws Exception
     * @return string backup directory paty
     */
    private function stageReplace($source_path, $target_path, $store_prev = false)
    {
        $target_path = self::formatPath($target_path);
        $source_path = self::formatPath($source_path);
        $this->writeLog(__METHOD__, self::LOG_TRACE, compact('source_path', 'target_path', 'store_prev'));
        $backup_path = false;
        $prev_backup_path = false;
        try {
            if (file_exists(self::$root_path.$target_path)) {
                $backup_path = self::$update_path.'backup/';
                $this->mkdir($backup_path);
                if ($store_prev) {
                    $backup_path .= $target_path.date('Y-m-d H-i-s');
                } else {
                    $backup_path .= $target_path;
                    if (file_exists(self::$root_path.$backup_path)) {
                        do {
                            $prev_backup_path = $backup_path.'.'.md5(time());
                        } while (!file_exists(self::$root_path.$backup_path));

                        if (!$this->rename($backup_path, $prev_backup_path)) {
                            $prev_backup_path = false;
                            throw new Exception("Error while rename old backup path {$backup_path}");
                        }
                    }
                }
                $backup_path = self::formatPath($backup_path);
            } else {
                $middle_path = preg_replace('@(^|/)[^/]+[/\\\\]+$@', '/', $target_path.'/');
                if ($middle_path) {
                    $this->mkdir($middle_path);
                }
            }
            if ($backup_path) {
                $this->mkdir(preg_replace('@/[^/]+[/\\\\]+$@', '/', $backup_path.'/'));
                if ($this->rename($target_path, $backup_path)) {
                    $this->writeLog(__METHOD__.' backup current version code', self::LOG_TRACE, compact('backup_path'));
                    if ((strpos($target_path, '.') !== false) && file_exists(self::$root_path.'.'.$target_path.'.md5')) {

                    }
                } else {
                    throw new Exception("Error on make backup for {$target_path} at {$backup_path}");
                }
            }
            if (strpos($target_path, '.') === false) {
                if ($this->rename($source_path, $target_path)) {
                    $this->writeLog(__METHOD__.' replace current version code', self::LOG_TRACE, compact('target_path'));
                } else {
                    //roll back rename
                    if ($backup_path) {
                        $this->rename($backup_path, $target_path);
                    }
                    throw new Exception("Error on update {$target_path} by {$source_path}");
                }
            } else {

            }
            if ($prev_backup_path) {
                try {
                    $this->cleanupPath($prev_backup_path);
                } catch (Exception $ex) {
                    $this->writeLog($ex->getMessage(), self::LOG_ERROR);
                }
            }
        } catch (Exception $ex) {
            try {
                if ($prev_backup_path) {
                    $this->cleanupPath($backup_path);
                    $this->rename($prev_backup_path, $backup_path);
                }
            } catch (Exception $ex) {
                $this->writeLog($ex->getMessage(), self::LOG_ERROR);
            }
            throw $ex;
        }
        return $backup_path;
    }

    private function rename($oldname, $newname)
    {
        $result = false;
        if (@rename(self::$root_path.$oldname, self::$root_path.$newname) || sleep(3) || @rename(self::$root_path.$oldname, self::$root_path.$newname)) {
            $result = true;
            $this->writeLog(__METHOD__.' replace current version code', self::LOG_TRACE, compact('oldname', 'newname'));
        }
        return $result;
    }

    /**
     * @param $paths
     * @return bool
     * @throws Exception
     */
    private function stageCleanup($paths)
    {
        foreach ((array)$paths as $path => $skip_directory) {
            if (!is_bool($skip_directory)) {
                $path = $skip_directory;
                $skip_directory = false;
            }
            $this->cleanupPath($path, $skip_directory);
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        return true;
    }

    private function stageUpdate()
    {
        return true;
    }

    /**
     *
     * Verify extracted files hashes
     * @param string $path
     * @param boolean $purge_obsolete_files
     * @param array $hash
     * @return bool
     * @throws Exception
     */
    private function stageVerify($path, $purge_obsolete_files = false, $hash = array())
    {
        try {
            $path = self::formatPath($path);
            $hash = $this->getHash($path, $hash);
            if ($dir = opendir(self::$root_path.$path)) {
                while (false !== ($name = readdir($dir))) {
                    if (($name != '.') && ($name != '..')) {
                        $relative_path = $path.'/'.$name;
                        $file = self::$root_path.$relative_path;
                        if (file_exists($file)) {

                            if (is_dir($file)) {
                                $this->stageVerify($relative_path, $purge_obsolete_files, $hash);
                            } elseif ($name != self::HASH_PATH) {
                                if (isset($hash[$relative_path])) {
                                    if (md5_file($file) != $hash[$relative_path]) {
                                        $this->writeLog("File {$relative_path} modified", self::LOG_WARNING);
                                    }
                                } else {
                                    $this->writeLog("File {$relative_path} is obsolete", self::LOG_WARNING);
                                    /* optional (re)move obsolete files */
                                    if ($purge_obsolete_files) {
                                        $this->writeLog("File {$relative_path} deleted ".(@unlink($file) ? 'success' : 'fail'), self::LOG_WARNING);
                                    }
                                }
                            }
                        } else {
                            throw new Exception("Not found {$relative_path}");
                        }
                    }
                }
                closedir($dir);
            } else {
                throw new Exception("Error during open {$path}");
            }
            return true;
        } catch (Exception $ex) {
            if (!empty($dir) && is_resource($dir)) {
                closedir($dir);
            }
            throw $ex;
        }
    }

    public static function obHandler($output)
    {
        if (!self::$ob_skip) {
            $instance = new self(self::LOG_WARNING);
            $state = $instance->getState();
            $instance->current_stage = $state['stage_name'].'_'.self::STATE_ERROR;
            $instance->current_chunk_id = $state['chunk_id'];
            $message = $output;

            if ($error = error_get_last()) {
                $message .= sprintf('%s @%s:%d', $error['message'], $error['file'], $error['line']);
            }
            $instance->writeLog(__METHOD__.' error : '.$message, self::LOG_ERROR);
            $state = array_merge($state, array('error' => $message, 'stage_status' => self::STATE_ERROR));
            $instance->setState($state);
        }
        return $output;
    }

    /**
     *
     * @param $paths
     * @param bool $skip_directory
     * @throws Exception
     * @return void
     */
    private function cleanupPath($paths, $skip_directory = false)
    {
        static $timestamp = 0;
        if (!$timestamp) {
            $timestamp = microtime(true);
        }
        foreach ((array)$paths as $path) {
            try {

                if (file_exists(self::$root_path.$path)) {
                    if ($dir = opendir(self::$root_path.$path)) {
                        while (false !== ($current_path = readdir($dir))) {
                            if (($current_path != '.') && ($current_path != '..')) {
                                if (is_dir(self::$root_path.$path.'/'.$current_path)) {
                                    $this->cleanupPath($path.'/'.$current_path, $skip_directory);
                                } else {
                                    if (!@unlink(self::$root_path.$path.'/'.$current_path)) {
                                        throw new Exception("Error on unlink file {$path}/{$current_path}");
                                    }
                                }
                            }
                            if (($current_timestamp = microtime(true)) && (($current_timestamp - $timestamp) > 3)) {
                                $timestamp = $current_timestamp;
                                $this->setState();
                            }
                        }
                        closedir($dir);
                        if (!@rmdir(self::$root_path.$path) && !$skip_directory) {
                            throw new Exception("Error on unlink directory {$path}");
                        }
                    } else {
                        throw new Exception("Error during open {$path}");
                    }
                }
            } catch (Exception $ex) {
                if (!empty($dir) && is_resource($dir)) {
                    closedir($dir);
                }
                throw $ex;
            }
        }
    }

    /**
     * Get current state details
     * @return array
     * @todo use structure for returned data
     */
    public function getState()
    {
        $state = array(
            'start_time'       => false,
            'start_time_float' => false,
            'elapsed_time'     => 0.0,
            'stage'            => 0,
            'stage_name'       => self::STAGE_NONE,
            'stage_status'     => 'wait',
            'heartbeat'        => false,
        );
        $path = self::$root_path.self::PATH_STATE;
        if (file_exists($path)) {
            if (($data = file_get_contents($path)) && ($data = base64_decode($data, true))) {
                $state = array_merge($state, (array)unserialize($data));
            } elseif (sleep(1) || ($data = file_get_contents($path)) && ($data = base64_decode($data, true))) {
                $state = array_merge($state, (array)unserialize($data));
            } elseif (sleep(1) || ($data = file_get_contents($path)) && ($data = base64_decode($data, true))) {
                $state = array_merge($state, (array)unserialize($data));
            } else {
                $state['stage_name'] = self::STAGE_NONE;
                $state['stage_status'] = self::STATE_ERROR;
                $state['error'] = 'Error while read state file';
            }
        } else {
            $state['stage_name'] = self::STAGE_NONE;
            $state['stage_status'] = 'wait';
        }

        return self::prepareState($state);
    }

    private static function prepareState($state)
    {
        //calculate elapsed and estimated_time
        if (isset($state['timestamp_float']) && isset($state['stage_start_time'])) {
            $state['stage_elapsed_time'] = $state['timestamp_float'] - $state['stage_start_time'];
            $state['elapsed_time'] = $state['timestamp_float'] - $state['start_time_float'];
        }
        //calculate progress
        if (isset($state['stage_value']) && isset($state['stage_current_value']) && $state['stage_value'] > 0) {
            $state['stage_progress'] = sprintf('%0.1f', min(100, 100 * $state['stage_current_value'] / $state['stage_value']));
        } else {
            $state['stage_progress'] = null;
        }

        if (isset($state['stage_value']) && isset($state['stage_current_value']) && ($state['stage_current_value'] > 0) && ($state['stage_value'] > 0)) {
            $state['stage_estimated_time'] = $state['stage_elapsed_time'] * $state['stage_value'] / $state['stage_current_value'];
            //$state['stage_estimated_time'] -= $state['stage_elapsed_time'];
        } else {
            $state['stage_estimated_time'] = false;
        }

        if (isset($state['stage_elapsed_time']) && ($state['stage_elapsed_time'] > 0) && isset($state['stage_current_value']) && ($state['stage_current_value'] > 0)) {
            $state['stage_performance_avg'] = $state['stage_current_value'] / $state['stage_elapsed_time'];
        } else {
            $state['stage_performance_avg'] = 0;
        }

        if (!isset($state['stage_performance'])) {
            $state['stage_performance'] = 0;
        }
        $state['stage_performance_formatted'] = '';
        $state['stage_performance_formatted'] .= self::formatPerformance($state['stage_performance']);

        $state['stage_performance_formatted'] .= ' / ';
        $state['stage_performance_formatted'] .= self::formatPerformance($state['stage_performance_avg']);

        $state['stage_performance_formatted'] .= ' (current/avg)';

        $current_time = microtime(true);
        if (isset($state['stage_start_time']) && $state['stage_start_time']) {
            $state['stage_elapsed_time'] = $current_time - $state['stage_start_time'];
        }
        if (isset($state['timestamp_float'])) {
            $state['heartbeat'] = $current_time - $state['timestamp_float'];
        }
        return $state;
    }

    private static function formatPerformance($performance)
    {
        $result = '';
        if ($performance > 1024 * 512) {
            $result .= sprintf('%0.2fMb/s', $performance / (1024 * 1024));
        } elseif ($performance > 512) {
            $result .= sprintf('%0.2fKb/s', $performance / 1024);
        } elseif ($performance > 0.5) {
            $result .= sprintf('%0.2fb/s', $performance);
        } else {
            $result .= '-';
        }
        return $result;
    }

    /**
     *
     * @param array $state_params
     * @return float
     * @throws Exception
     */
    public function setState($state_params = array())
    {
        static $state = array();
        $escalate_log_level = false;
        if (!$state) {
            $state = array(
                'start_time'       => time(),
                'start_time_float' => microtime(true),
                'elapsed_time'     => 0.0,
                'stage'            => 0,
                'stage_name'       => 'unkown_none',
                'state_heartbeat'  => false,
                'thread_id'        => $this->thread_id,
                'chunk_id'         => null,

            );
        }
        if (strpos($this->current_stage, '_') === false) {
            $stage_name = 'unknown';
            $stage_status = self::STAGE_NONE;
        } else {
            list($stage_name, $stage_status) = explode('_', $this->current_stage, 2);
        }

        $default = array(
            'chunk_id'         => $this->current_chunk_id,
            'stage_status'     => $stage_status,
            'timestamp'        => time(),
            'timestamp_float'  => microtime(true),
            'stage_start_time' => microtime(true)
        );
        if ($stage_status == self::STATE_COMPLETE) {
            $escalate_log_level = true;
        }
//        $state_params = array_merge($state_params, $default);
        $state_params = array_merge($default, $state_params);
        if ($this->current_stage && (($state['chunk_id'] != $state_params['chunk_id']) || ($state['stage_name'] != $stage_name))) {
            if ($state['stage_name'] != $stage_name) {
                $state['stage']++;
            }
            $state['chunk_id'] = $state_params['chunk_id'];
            $escalate_log_level = true;
            $state['stage_name'] = $stage_name;
            $state_params['datetime'] = date('r', intval($state_params['timestamp_float']));
            if (!isset($state_params['stage_value'])) {
                $state_params['stage_value'] = 0;
            }
            if (!isset($state_params['stage_current_value'])) {
                $state_params['stage_current_value'] = 0;
            }
            $state['stage_current_value'] = 0;
        } else {
            if (
                isset($state['stage_current_value'])
                && isset($state_params['stage_current_value'])
                && !empty($state['timestamp_float'])
                && !empty($state_params['timestamp_float'])
            ) {
                $state_params['stage_performance'] = ($state_params['timestamp_float'] - $state['timestamp_float']);
                if ($state_params['stage_performance']) {
                    $state_params['stage_performance'] = ($state_params['stage_current_value'] - $state['stage_current_value']) / $state_params['stage_performance'];
                }
            } else {
                $state_params['stage_performance'] = 0;
            }
        }

        if (isset($state['timestamp_float'])) {
            $state['state_heartbeat'] = microtime(true) - $state['timestamp_float'];
        }

        $state = array_merge($state, $state_params);

        $this->writeLog(__METHOD__, $escalate_log_level ? self::LOG_TRACE : self::LOG_DEBUG, $state);
        //TODO move next calculate code into getState()
        if ($state_handler = $this->fopen(self::PATH_STATE, 'w')) {
            fwrite($state_handler, base64_encode(serialize($state)));
            $this->fclose($state_handler);
        }
        $this->setFullState($state);
        return isset($state_params['stage_performance']) ? intval(1024 * ceil($state_params['stage_performance'] / 1024)) : 0;
    }

    private function setFullState($state)
    {
        if (is_null($state)) {
            $fstate = array();
        } else {
            $fstate = $this->getFullState();
            $slug = $state['chunk_id'];
            $stage = $state['stage'];
            if (!isset($fstate[$slug])) {
                $fstate[$slug] = array();
            } elseif ($stage == 1) {
                $fstate[$slug] = array();
            }
            $fstate[$slug][$state['stage']] = self::prepareState($state);
        }
        if ($state_handler = $this->fopen(self::PATH_FSTATE, 'w')) {
            fwrite($state_handler, base64_encode(serialize($fstate)));
            $this->fclose($state_handler);
        }
    }

    public function getFullState($mode = 'apps')
    {
        $path = self::$root_path.self::PATH_FSTATE;
        $fstate = array();
        if (file_exists($path)) {
            if (($data = file_get_contents($path)) && ($data = base64_decode($data, true))) {
                $fstate = (array)unserialize($data);
            }
        }
        //HACK for PHP < 5.2.14 - json_encode work incorrect for float
        array_walk_recursive($fstate, array($this, 'getFullStateCallback'));
        switch ($mode) {
            case 'stage':
                $state_ = array();
                foreach ($fstate as $app => $stages) {
                    foreach ($stages as $stage => $info) {
                        if (!isset($state_[$stage])) {
                            $state_[$stage] = array();
                        }
                        $state_[$stage][$app] = $info;
                    }
                }
                $fstate = $state_;
                break;
            case 'raw':
                $state_ = array();
                foreach ($fstate as $app => $stages) {
                    foreach ($stages as $stage => $info) {
                        if (!isset($state_[$stage])) {
                            $state_[$stage] = array();
                        }
                        $state_[$stage][$app] = $info;
                    }
                }
                $state__ = array();
                foreach ($state_ as $stages) {
                    foreach ($stages as $info) {
                        $state__[] = $info;
                    }
                }
                $fstate = $state__;
                break;
            case 'apps':
            default:
                //nothing to convert
                break;
        }
        return $fstate;
    }

    private function getFullStateCallback(&$val, $key)
    {
        $val = preg_match("/^-?\d+(\.|,)\d+$/", $val) ? intval($val) : $val;
    }

    private function skipPath($path)
    {
        return false && (preg_match('/(\.update$|\.backup$|\.backup\.[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}-[\d]{2}-[\d]{2}$)/', $path) ? true : false);
    }

    private static function formatPath($path)
    {
        $path = preg_replace('@([/\\\\]+)@', '/', $path);
        return preg_replace('@([/\\\\]+)$@', '', $path);
    }

    private function getHash($path, $hash = array())
    {

        $hash_path = self::$root_path.$path.'/'.self::HASH_PATH;
        if (file_exists($hash_path)) {
            if ($hashes = explode("\n", file_get_contents($hash_path))) {
                foreach ($hashes as $line) {
                    if ($line && preg_match('/^([0-9a-f]{32})\s+\*(.*)$/', $line, $matches)) {
                        $hash[$path.'/'.$matches[2]] = $matches[1];
                    }
                }
                $this->writeLog(var_export($hash, true), self::LOG_DEBUG);
            }
        }
        return $hash;
    }

    /**
     * @param string $message
     * @param int    $log_level
     * @param mixed  $debug_data
     * @throws Exception
     */
    private function writeLog($message, $log_level = self::LOG_WARNING, $debug_data = null)
    {
        static $log_counter = 0;
        $names = array(
            self::LOG_DEBUG   => 'DEBUG',
            self::LOG_TRACE   => 'TRACE',
            self::LOG_WARNING => 'WARNING',
            self::LOG_ERROR   => 'ERROR',
        );
        if ($log_level >= $this->log_level) {
            $this->mkdir(dirname(self::PATH_LOG));
            $log_level_name = isset($names[$log_level]) ? $names[$log_level] : '-';
            //TODO add date log modifier
            if ($this->log_handler || ($this->log_handler = fopen(self::$root_path.self::PATH_LOG, ($this->log_level > self::LOG_DEBUG ? 'w' : 'a')))) {
                $memory_usage = function_exists('memory_get_usage') ? sprintf('%0.2fMb', memory_get_usage() / 1048576) : 'unknown';
                $memory_peak = function_exists('memory_get_peak_usage') ? sprintf('%0.2fMb', memory_get_peak_usage() / 1048576) : 'unknown';
                if ($debug_data) {
                    $debug_data = "\n{".str_repeat('-', 60)."\n".var_export($debug_data, true)."\n}".str_repeat('-', 60);
                    $debug_data = preg_replace('@([\?&](hash|token|previous_hash)=)([^&\?]+)@', '$1*hash*', $debug_data);
                }
                $log = date('c');
                $log .= sprintf('%05d', ++$log_counter);
                $log .= "\t{$this->thread_id}\t{$log_level_name}\t{$memory_usage}\t{$memory_peak}\t{$this->current_stage}\n";
                $log .= "{$message}".$debug_data."\n";
                fwrite($this->log_handler, $log);
            }
        }
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function protect($path)
    {
        if (preg_match('`^(wa-data/protected|wa-log|wa-cache|wa-config)/`', $path, $matches)) {
            $htaccess = $matches[1].'/.htaccess';
            if (!file_exists(self::$root_path.$htaccess)) {
                $fp = @fopen(self::$root_path.$htaccess, 'w');
                if ($fp) {
                    fwrite($fp, "Deny from all\n");
                    fclose($fp);
                    $this->writeLog(__METHOD__, self::LOG_DEBUG, compact('path', 'htaccess'));
                } else {
                    $this->writeLog(__METHOD__, self::LOG_ERROR, compact('path', 'htaccess'));
                    throw new Exception("Error while attempt protect {$path}");
                }
            }
        }
    }

    /**
     * @param string $pathname
     * @param int $mode
     * @throws Exception
     */
    private function mkdir($pathname, $mode = 0777)
    {
        $success = false;
        try {
            if (!file_exists(self::$root_path.$pathname)) {
                if (!mkdir(self::$root_path.$pathname, $mode & 0777, true)) {
                    throw new Exception("Error on make dir {$pathname}");
                } else {
                    $this->writeLog(__METHOD__, self::LOG_DEBUG, compact('pathname', 'mode'));
                    $success = true;
                    $this->protect($pathname);
                }
            } elseif (!is_dir(self::$root_path.$pathname)) {
                throw new Exception("Error on make dir {$pathname} - it's a file");

            } elseif (!is_writable(self::$root_path.$pathname)) {
                throw new Exception("Error on access {$pathname} write forbidden");
            }
        } catch (Exception $e) {
            if (!$success) {
                $this->writeLog(__METHOD__, self::LOG_ERROR, compact('pathname', 'mode'));
            }
            throw $e;
        }
    }

    /**
     * Callback method for adjust stage options
     * @param $current_chunk
     * @param $performance
     * @param $stage
     * @param $multiplier
     * @param $min_performance
     * @param $max_performance
     * @return int
     * @throws Exception
     */
    public function adjustStageChunk($current_chunk, $performance, $stage, $multiplier = null, $min_performance = 1, $max_performance = null)
    {
        static $stack_length = 5;
        static $performance_data = array();
        if (!isset($performance_data[$stage])) {
            $performance_data[$stage] = array();
        }
        $performance_data[$stage][] = $performance;
        if (count($performance_data[$stage]) > $stack_length) {
            array_shift($performance_data[$stage]);
            $calculated_chunk = array_sum($performance_data[$stage]) / $stack_length;
            $this->writeLog(__METHOD__, self::LOG_DEBUG, compact('current_chunk', 'calculated_chunk', 'performance_data', 'stack_length'));
            $calculated_chunk = min($calculated_chunk, $current_chunk * 2);
            $calculated_chunk = max($calculated_chunk, $current_chunk / 2);
        } else {
            $calculated_chunk = $current_chunk;
        }
        if ($max_performance) {
            $calculated_chunk = min($calculated_chunk, $max_performance);
        }
        $calculated_chunk = max(1, $calculated_chunk, $min_performance);

        if ($multiplier) {
            $calculated_chunk = $multiplier * ceil($calculated_chunk / $multiplier);
        }
        return $calculated_chunk;
    }

    private function curlAvailable()
    {
        return extension_loaded('curl') && function_exists('curl_init');
    }

    /**
     * @param string $url
     * @param array  $curl_options
     * @return false|resource|null
     * @throws Exception
     */
    private function getCurl($url, $curl_options = array())
    {
        $ch = null;
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new Exception(_w('err_curlinit'));
        }
        if (!($ch = curl_init())) {
            throw new Exception(_w('err_curlinit'));
        }

        if (curl_errno($ch) != 0) {
            throw new Exception(_w('err_curlinit').curl_errno($ch).' '.curl_error($ch));
        }
        if (!is_array($curl_options)) {
            $curl_options = array();
        }
        $curl_default_options = array(
            CURLOPT_HEADER            => 0,
            CURLOPT_RETURNTRANSFER    => 1,
            CURLOPT_TIMEOUT           => self::TIMEOUT_SOCKET * 60,
            CURLOPT_CONNECTTIMEOUT    => self::TIMEOUT_SOCKET,
            CURLOPT_DNS_CACHE_TIMEOUT => 3600,
            CURLOPT_BINARYTRANSFER    => true,
            CURLOPT_WRITEFUNCTION     => array(&$this, 'curlWriteHandler'),
            CURLOPT_HEADERFUNCTION    => array(&$this, 'curlHeaderHandler'),
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

        if (preg_match('@^https://@', $url) && (DIRECTORY_SEPARATOR === '\\')) {
            $curl_options[CURLOPT_SSL_VERIFYHOST] = false;
            $curl_options[CURLOPT_SSL_VERIFYPEER] = false;
        }

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
        return $ch;
    }

    /**
     * @param resource $source_stream
     * @param int      $download_content_length
     * @return array
     * @throws Exception
     */
    private function getStreamInfo($source_stream, $download_content_length = 4096)
    {
        $stream_meta_data = stream_get_meta_data($source_stream);

        //read data chunk to determine stream meta data
        $buf = stream_get_contents($source_stream, $download_content_length);

        $stream_seekable = isset($stream_meta_data['seekable']) ? $stream_meta_data['seekable'] : false;

        $headers = array();
        if (isset($stream_meta_data["wrapper_data"]["headers"])) {
            $headers = $stream_meta_data["wrapper_data"]["headers"];
        } elseif (isset($stream_meta_data["wrapper_data"])) {
            $headers = $stream_meta_data["wrapper_data"];
        }

        $header_matches = null;
        $content_length = null;
        foreach ($headers as $header) {
            if (preg_match('@content-length:\s*\b(\d+)\b@i', $header, $header_matches)) {
                $content_length = intval($header_matches[1]);
                break;
            }
        }

        $status = 200;
        $status_description = 'none';
        //check server response codes (500/404/403/302/301/etc)
        foreach ($headers as $header) {
            if (preg_match('@http/\d+\.\d+\s+(\d+)\s+(.+)$@i', $header, $header_matches)) {
                $status = intval($header_matches[1]);
                $status_description = trim($header_matches[2]);
                if ($status != 200) {
                    throw new Exception("Invalid server response with code {$status} ($status_description)");
                }
                break;
            }
        }

        $debug_data = array(
            'stream_meta_data' => $stream_meta_data,
            'headers'          => $headers,
            'content_length'   => $content_length,
            'status'           => "{$status} ({$status_description})",
            'seekable'         => $stream_seekable,
        );

        $this->writeLog(__METHOD__.' :Source file headers', self::LOG_DEBUG, $debug_data);
        return array($content_length, $download_content_length, $buf);
    }

    /**
     * @param string $file
     * @param string $mode
     * @param int    $retry
     * @return bool|resource
     * @throws Exception
     */
    private function fopen($file, $mode, $retry = 5)
    {
        $this->mkdir(dirname($file));
        while (!($fp = @fopen(self::$root_path.$file, $mode)) || !@flock($fp, LOCK_EX)) {
            if ($fp) {
                fclose($fp);
            }
            if (--$retry > 0) {
                sleep(1);
            } else {
                break;
            }
        }
        return $fp;
    }

    private function fclose($fp)
    {
        if ($fp && is_resource($fp)) {
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function __destruct()
    {
        if ($this->log_handler) {
            $this->writeLog(__METHOD__.' called'."\n\n\n", self::LOG_TRACE);
            fclose($this->log_handler);
        }
    }

    /**
     * autoload handler for dependent internal classes
     * @param $class_name string
     * @return boolean
     */
    public static function autoload($class_name)
    {
        $class_name = strtolower($class_name);
        $result = false;
        if (isset(self::$depended_classes[$class_name])) {
            require_once self::$root_path.self::$depended_classes[$class_name];
            $result = true;
        }
        return $result;
    }

    private static function debugBacktraceCustom($names = array('object', 'args'))
    {
        $path = str_replace('\\', '/', realpath(dirname(__FILE__).'/../../../'));
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        if (is_array($names) && $names) {
            foreach ($backtrace as & $backtrace_item) {
                $backtrace_item['file'] = str_replace('', '', str_replace($path, '', str_replace('\\', '/', $backtrace_item['file']))).':'.$backtrace_item['line'];
                unset($backtrace_item['line']);
                if (isset($backtrace_item['type']) && isset($backtrace_item['class'])) {
                    if (isset($backtrace_item['object']) && (($class = get_class($backtrace_item['object'])) != $backtrace_item['class'])) {
                        $backtrace_item['class'] = "{$class}({$backtrace_item['class']})";
                    }
                    $args = array();
                    foreach ($backtrace_item['args'] as $arg) {
                        if (is_object($arg)) {
                            $args[] = get_class($arg);
                        } elseif (is_array($arg)) {
                            $args[] = 'Array('.count($arg).')';
                        } else {
                            //$args[] = var_export($arg,true);
                        }
                    }
                    $backtrace_item['function'] = $backtrace_item['class'].$backtrace_item['type'].$backtrace_item['function'].'('.implode(', ', $args).')';
                    unset($backtrace_item['class']);
                    unset($backtrace_item['type']);
                }

                foreach ($names as $name) {
                    if (isset($backtrace_item[$name])) {
                        unset($backtrace_item[$name]);
                    }
                }
            }
        }
        return $backtrace;
    }

    /**
     * Get attributes of the file
     *
     * @param $pathFile
     * @return string
     */
    private function fileInfo($pathFile)
    {
        $fileInfo = '';
        if (file_exists($pathFile)) {
            $fileInfo = $pathFile.' '.decoct(fileperms($pathFile))
                .' '.filesize($pathFile)
                .' '.date('d-m-Y H:i:s', filemtime($pathFile));
        }
        return $fileInfo;
    }
}
