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
 * @subpackage config
 */

class waSystemConfig
{
    protected $root_path = null;
    protected $environment = null;
    protected static $root_url = null;

    protected static $active = null;
    protected static $debug = false;

    protected static $system_options = array(
        'backend_url' => 'webasyst',
        'mod_rewrite' => true,
        'cache_versioning' => true,
    );

    public static $time = null;

    protected $factories = array();

    protected static $helpers = false;

    protected $cache = null;

    public function __construct($environment = null, $root_path = null)
    {
        if (empty(self::$time)) {
            self::$time = microtime(true);
        }
        if (self::$active == null || $this instanceof waAppConfig) {
            self::$active = $this;
        }

        if ($root_path === null) {
            $root_path = dirname(__FILE__).'/../..';
        }
        $this->root_path = realpath($root_path);
        if (!waConfig::has('wa_path_root')) {
            $this->setPath($this->root_path);
        }

        if (!self::$helpers) {
            self::$helpers = true;
            include($this->getRootPath()."/wa-system/helper/load.php");
        }

        $this->environment = $environment;
        $this->configure();
        $this->init();

        if ($this->environment === null) {
            $url = explode("/", $this->getRequestUrl(true, true));
            $url = $url[0];
            $this->environment = $url === $this->getSystemOption('backend_url') ? 'backend' : 'frontend';
        }
    }

    public static function getTime($diff = true)
    {
        if ($diff) {
            return microtime(true) - self::$time;
        } else {
            return self::$time;
        }
    }

    protected function getSystemOption($name)
    {
        return self::systemOption($name);
    }

    public static function systemOption($name)
    {
        return isset(self::$system_options[$name]) ? self::$system_options[$name] : null;
    }

    protected function getOption($name = null)
    {
        return $this->getSystemOption($name);
    }

    public function getFactory($name)
    {
        return isset($this->factories[$name]) ? $this->factories[$name] : null;
    }


    public function getCurrentUrl()
    {
        return waRequest::server('REQUEST_URI', '/');
    }

    public function getRequestUrl($without_root = true, $without_params = false)
    {
        $url = waRequest::server('REQUEST_URI', '/');
        if ($without_root) {
            $url = (string) substr($url, strlen($this->getRootUrl()));
        }
        if (!$this->getSystemOption('mod_rewrite')) {
            if (substr($url, 0, 9) == 'index.php') {
                $url = substr($url, 10);
            }
        }
        if ($without_params) {
            if (($i = strpos($url, '?')) !== false) {
                return substr($url, 0, $i);
            }
        }
        return $url;
    }


    public function getRootUrl($absolute = false, $script = false)
    {
        if (!self::$root_url) {
            if ($this->environment !== 'cli') {
                if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
                    self::$root_url = $_SERVER['SCRIPT_NAME'];
                } elseif (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']) {
                    self::$root_url = $_SERVER['PHP_SELF'];
                }
                self::$root_url = '/'.ltrim(self::$root_url, '/');
                self::$root_url = preg_replace('!/[^/]*$!', '/', self::$root_url);
            }
            if (!self::$root_url) {
                self::$root_url = $this->systemOption('default_root_url');
            }
            if (!self::$root_url) {
                self::$root_url = '/';
            }
        }
        if ($absolute) {
            $url = $this->getHostUrl();
            return $url.self::$root_url.($script && !$this->getSystemOption('mod_rewrite') ? 'index.php/' : '');
        }
        return self::$root_url.($script && !$this->getSystemOption('mod_rewrite') ? 'index.php/' : '');
    }

    public function getHostUrl()
    {
        if (waRequest::isHttps()) {
            $proto = 'https://';
        } else {
            $proto = 'http://';
        }

        $host = waRequest::server('HTTP_HOST', $this->systemOption('default_host_domain'), 'string');
        $host = ifempty($host, 'localhost');
        return $proto.$host;
    }

    public function getDomain()
    {
        $domain = waRequest::server('HTTP_HOST');
        $u = trim(waSystem::getInstance()->getRootUrl(false, true), '/');
        if ($u) {
            $domain .= '/'.$u;
        }
        return $domain;
    }

    protected function configure()
    {
        if (!extension_loaded('mbstring') && !function_exists('mb_strlen')) {
            die('PHP extension mbstring required');
        }
        @mb_internal_encoding('UTF-8');
        @ini_set('default_charset', 'utf-8');

        // Make sure setlocale() is set to something utf8-compatible if at all possible
        $sysloc = setlocale(LC_COLLATE, 0);
        if (false === stripos($sysloc, 'utf-8') && false === stripos($sysloc, 'utf8')) {
            $sysloc = explode('.', $sysloc, 2);
            if (count($sysloc) < 2) {
                $loc = 'en_US';
            } else {
                $loc = $sysloc[0];
            }

            $possible_locales = array();
            $lang = preg_replace('~_.*~', '', $loc);
            foreach(array($loc, $lang, 'en_US') as $locale) {
                foreach(array('utf8','utf-8','UTF8','UTF-8') as $enc) {
                    $possible_locales[] = $locale.'.'.$enc;
                }
            }
            setlocale(LC_ALL, $possible_locales);
        }

        // Always use dot separator when formatting floats
        setlocale(LC_NUMERIC, 'C');

        @ini_set('register_globals', 'off');
        // magic quotes
        @ini_set("magic_quotes_runtime", 0);
        if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
            @set_magic_quotes_runtime(false);
        }
        // IIS
        if ($this->environment !== 'cli' && !isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
            if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
            }
            self::$system_options['mod_rewrite'] = false;
        }

        if (!ini_get('magic_quotes_gpc')) {
            return;
        }

        function array_stripslashes($array)
        {
            return is_array($array) ? array_map("array_stripslashes", $array) : stripslashes($array);
        }

        $_GET = array_stripslashes($_GET);
        $_POST = array_stripslashes($_POST);
        $_COOKIE = array_stripslashes($_COOKIE);
        $_REQUEST = array_stripslashes($_REQUEST);
    }

    public function init()
    {
        $file_path = $this->getPath('config').'/config.php';
        if (file_exists($file_path)) {
            $config = include($file_path);
            foreach ($config as $name => $value) {
                self::$system_options[$name] = $value;
            }

            if (!empty(self::$system_options['cache_versioning'])) {
                $this->enableCacheVersioning();
            }
        }
    }

    public function getBackendUrl($with_root_url = false)
    {
        if (!$this->getSystemOption('mod_rewrite')) {
            $backend_url = 'index.php/'.$this->getSystemOption('backend_url');
        } else {
            $backend_url = $this->getSystemOption('backend_url');
        }
        if ($with_root_url) {
            return $this->getRootUrl().$backend_url."/";
        } else {
            return $backend_url;
        }
    }

    protected function setPath($root_path)
    {
        $this->root_path = $root_path;
        waConfig::add(array(
            'wa_path_root'      => $root_path,
            'wa_path_apps'      => $root_path.DIRECTORY_SEPARATOR.'wa-apps',
            'wa_path_system'    => $root_path.DIRECTORY_SEPARATOR.'wa-system',
            'wa_path_log'       => $root_path.DIRECTORY_SEPARATOR.'wa-log',
            'wa_path_data'      => $root_path.DIRECTORY_SEPARATOR.'wa-data',
            'wa_path_content'   => $root_path.DIRECTORY_SEPARATOR.'wa-content',
            'wa_path_config'    => $root_path.DIRECTORY_SEPARATOR.'wa-config',
            'wa_path_cache'     => $root_path.DIRECTORY_SEPARATOR.'wa-cache',
            'wa_path_plugins'   => $root_path.DIRECTORY_SEPARATOR.'wa-plugins',
            'wa_path_installer' => $root_path.DIRECTORY_SEPARATOR.'wa-installer',
            'wa_path_widgets'   => $root_path.DIRECTORY_SEPARATOR.'wa-widgets',
        ));
    }

    protected function enableCacheVersioning()
    {
        $wa_cache = waConfig::get('wa_path_cache_root');
        if (!$wa_cache) {
            $wa_cache = waConfig::get('wa_path_cache', $this->root_path.DIRECTORY_SEPARATOR.'wa-cache');
            waConfig::set('wa_path_cache_root', $wa_cache);
        }
        $wa_cache .= DIRECTORY_SEPARATOR;

        $versioning_file = $wa_cache.'versioning';
        if (file_exists($versioning_file)) {
            $filemtime = filemtime($versioning_file);
        } else {
            waFiles::create($wa_cache, true);
            if (touch($versioning_file)) {
                $filemtime = time();
            } else {
                self::$system_options['cache_versioning'] = false;
                $filemtime = false;
            }
        }
        waConfig::set('wa_path_cache', $wa_cache.$this->getVersionedCacheDir($filemtime));
    }

    protected function getVersionedCacheDir($reset_time)
    {
        if (!$reset_time) {
            return '';
        }
        return substr(dechex($reset_time), -6);
    }

    public function getPath($name, $file = null)
    {
        $path = waConfig::get('wa_path_'.$name);
        if ($path) {
            if ($file) {
                $path .= DIRECTORY_SEPARATOR.$file.".php";
            }
        }
        return $path;
    }

    public function getAppsPath($app, $path = null)
    {
        if ($app == 'webasyst') {
            return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-system'.DIRECTORY_SEPARATOR.$app.($path ? DIRECTORY_SEPARATOR.$path : '');
        } else {
            return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-apps'.DIRECTORY_SEPARATOR.$app.($path ? DIRECTORY_SEPARATOR.$path : '');
        }
    }


    public function getConfigFile($file, $default = array())
    {
        $path = $this->getPath('config', $file);
        if (file_exists($path)) {
            $config_cache = waConfigCache::getInstance();
            $result = $config_cache->includeFile($path);
            if (empty($result)) {
                return $default;
            }
            return $result;
        } else {
            return $default;
        }
    }

    public function getConfigPath($name, $user_config = true, $app = null)
    {
        if ($app === null) {
            $app = 'webasyst';
        }
        if ($user_config) {
            $path = $this->getPath('config').'/apps/'.$app.'/'.$name;
            waFiles::create($path);
            return $path;
        } else {
            $path_name = ($app == 'webasyst') ? 'system' : 'apps';
            return $this->getPath($path_name).'/'.$app.'/lib/config/'.$name;
        }
    }

    public function getRouting()
    {
        return $this->getConfigFile('routing');
    }

    public function getSms()
    {
        return $this->getConfigFile('sms');
    }

    public function setSms($data)
    {
        if (waConfig::get('is_template')) {
            return false;
        }
        $path = $this->getPath('config', 'sms');
        if (waUtils::varExportToFile($data, $path)) {
            return true;
        }
        return false;
    }

    public function getMail()
    {
        return $this->getConfigFile('mail');
    }

    public function setMail($data)
    {
        if (waConfig::get('is_template')) {
            return false;
        }
        $path = $this->getPath('config', 'mail');
        if (waUtils::varExportToFile($data, $path)) {
            return true;
        }
        return false;
    }

    public function getAuth()
    {
        $cache = new waRuntimeCache('wa-config/auth');
        if ($cache->isCached()) {
            return $cache->get();
        } else {
            $data = $this->getConfigFile('auth');
            $cache->set($data);
            return $data;
        }
    }

    public function getBackendAuth()
    {
        $cache = new waRuntimeCache('wa-config/backend_auth');
        if ($cache->isCached()) {
            return $cache->get();
        } else {
            $data = $this->getConfigFile('backend_auth');
            $cache->set($data);
            return $data;
        }
    }

    public function setAuth($data)
    {
        if (waConfig::get('is_template')) {
            return false;
        }
        $path = $this->getPath('config', 'auth');
        if (waUtils::varExportToFile($data, $path)) {
            $cache = new waRuntimeCache('wa-config/auth');
            $cache->set($data);
            return true;
        }
        return false;
    }

    public function setBackendAuth($data)
    {
        if (waConfig::get('is_template')) {
            return false;
        }
        $path = $this->getPath('config', 'backend_auth');
        if (waUtils::varExportToFile($data, $path)) {
            $cache = new waRuntimeCache('wa-config/backend_auth');
            $cache->set($data);
            return true;
        }
        return false;
    }

    public function getRootPath()
    {
        return $this->root_path;
    }


    public static function getActive()
    {
        if (!self::hasActive()) {
            throw new RuntimeException('There is no active configuration.');
        }

        return self::$active;
    }

    public static function hasActive()
    {
        return null !== self::$active;
    }

    /**
     * Returns instance of AppConfig
     *
     * @param string $application
     * @param string $environment
     * @param string $root_path
     * @param string $locale
     * @throws waException
     * @return waAppConfig
     */
    public static function getAppConfig($application, $environment = null, $root_path = null, $locale = null)
    {
        $class_name = $application.'Config';
        if ($root_path === null) {
            $root_path = waConfig::get('wa_path_root');
        }

        if ($environment === null) {
            $environment = waSystem::getInstance()->getEnv();
        }

        if ($application === 'webasyst') {
            require_once($root_path.'/wa-system/webasyst/lib/config/webasystConfig.class.php');
            return new webasystConfig($environment, $root_path);
        }

        if (file_exists($file = $root_path.'/wa-apps/'.$application.'/lib/config/'.$class_name.'.class.php')) {
            require_once($file);
            return new $class_name($environment, $root_path, $application, $locale);
        } elseif (file_exists($file = $root_path.'/wa-apps/'.$application.'/lib/config/app.php')) {
            return new waAppConfig($environment, $root_path, $application, $locale);
        } else {
            throw new waException(sprintf('Application "%s" does not exist.', $application));
        }
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getDatabase()
    {
        $file = $this->getPath('config', 'db');
        if (!file_exists($file)) {
            throw new waException("File wa-config/db.php not found.", 600);
        }
        return include($file);
    }

    public static function isDebug()
    {
        return isset(self::$system_options['debug']) ? self::$system_options['debug'] : false;
    }

    public static function setDebug()
    {

    }

    public function getLocales($type = false)
    {
        return waLocale::getAll($type);
    }

    public function getCache($type = 'default')
    {
        if ($this->cache === null) {
            $file_path = $this->getPath('config', 'cache');
            if (file_exists($file_path)) {
                $cache_config = include($file_path);
                if (isset($cache_config[$type])) {
                    $options = $cache_config[$type];
                    $cache_type = $options['type'];
                    $cache_class = 'wa'.ucfirst($cache_type).'CacheAdapter';

                    try {
                        $cache_adapter = new $cache_class($options);
                        if ($this instanceof waAppConfig) {
                            $this->cache = new waCache($cache_adapter, $this->application);
                        } else {
                            $this->cache = new waCache($cache_adapter, 'wa-system');
                        }
                    } catch (waException $e) {
                        waLog::log($e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                    }
                }
            }
            if (!$this->cache) {
                $this->cache = false;
            }
        }
        return $this->cache;
    }

    public function clearCache()
    {
        $new_cache_dir = $old_cache_dir = waConfig::get('wa_path_cache');
        $wa_cache = waConfig::get('wa_path_cache_root');
        if (!$wa_cache) {
            self::$system_options['cache_versioning'] = false;
            $wa_cache = $old_cache_dir;
        }

        // When cache versioning is enabled, switch to new cache directory
        if (!empty(self::$system_options['cache_versioning'])) {
            if (touch($wa_cache.DIRECTORY_SEPARATOR.'versioning')) {
                $filemtime = time();
            } else {
                self::$system_options['cache_versioning'] = false;
                $filemtime = false;
            }

            $new_cache_dir = $wa_cache.DIRECTORY_SEPARATOR.$this->getVersionedCacheDir($filemtime);
            waConfig::set('wa_path_cache', $new_cache_dir);
        }

        // Delete all dirs inside wa-cache
        $clean = true;
        foreach (waFiles::listdir($wa_cache) as $path) {
            $path = $wa_cache.DIRECTORY_SEPARATOR.$path;
            if (!waSystemConfig::isDebug() && !empty(self::$system_options['cache_versioning']) && ($old_cache_dir == $path || $new_cache_dir == $path)) {
                // When cache versioning is enabled, do not delete current (both old and new) cache dirs.
                // Old because there might still be scripts running. New because it is supposed to be empty.
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }
            try {
                waFiles::delete($path, true);
            } catch (waException $ex) {
                if (empty(self::$system_options['cache_versioning'])) {
                    // we only care about leftovers here
                    // if cache versioning is disabled
                    $clean = false;
                }
            }
        }

        // Clear non-file-based app caches
        $apps = wa()->getApps(true);
        foreach ($apps as $app_id => $app) {
            try {
                $cache = wa()->getCache('default', $app_id);
                if ($cache) $cache->deleteAll();
            } catch (waException $ex) {
                $clean = false;
            }
        }

        // Make sure opcache and filesystem are aware of changes
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        @clearstatcache();

        return $clean;
    }
}
