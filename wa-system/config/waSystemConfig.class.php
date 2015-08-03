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
        'mod_rewrite' => true
    );

    public static $time = null;

    protected $factories = array();

    protected static $helpers = false;


    public function __construct($environment = null, $root_path = null)
    {
        self::$time = microtime(true);
        if (self::$active == null || $this instanceof waAppConfig) {
            self::$active = $this;
        }


        if ($root_path === null) {
            $this->root_path = realpath(dirname(__FILE__).'/../..');
        } else {
            $this->root_path = realpath($root_path);
        }
        $this->setPath($this->root_path);

        if (!self::$helpers) {
            self::$helpers = true;
            include($this->getRootPath()."/wa-system/helper/load.php");
        }

        $this->configure();
        $this->init();

        if ($environment === null) {
            $url = explode("/", $this->getRequestUrl(true, true));
            $url = $url[0];

            $this->environment = $url === $this->getSystemOption('backend_url') ? 'backend' : 'frontend';
        } else {
            $this->environment = $environment;
        }

        $url = $this->getRequestUrl();
        if ($url === 'robots.txt' || $url === 'favicon.ico' || $url == 'apple-touch-icon.png') {
            $this->responseStatic($url);
        }
    }

    protected function responseStatic($file)
    {
        $domain = waRequest::server('HTTP_HOST');
        $u = trim($this->getRootUrl(false, true), '/');
        if ($u) {
            $domain .= '/'.$u;
        }
        $path = waConfig::get('wa_path_data').'/public/site/data/'.$domain.'/'.$file;
        if (!file_exists($path)) {
            if (substr($domain, 0, 4) == 'www.') {
                $domain = substr($domain, 4);
            } else {
                $domain = 'www.'.$domain;
            }
            $path = waConfig::get('wa_path_data').'/public/site/data/'.$domain.'/'.$file;
        }
        if (file_exists($path)) {
            $file_type = waFiles::getMimeType($file);
            header("Content-type: {$file_type}");
            @readfile($path);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        exit;
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
            $url = substr($url, strlen($this->getRootUrl()));
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
            if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
                self::$root_url = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']) {
                self::$root_url = $_SERVER['PHP_SELF'];
            } else {
                self::$root_url = '/';
            }
            self::$root_url = preg_replace('!/[^/]*$!', '/', self::$root_url);
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
            $url = 'https://';
        } else {
            $url = 'http://';
        }
        $url .= waRequest::server('HTTP_HOST');
        return $url;
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

        @ini_set('register_globals', 'off');
        // magic quotes
        @ini_set("magic_quotes_runtime", 0);
        if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
            @set_magic_quotes_runtime(false);
        }
        // IIS
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
            if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
            }
            self::$system_options['mod_rewrite'] = false;
        }

        if (!get_magic_quotes_gpc()) {
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
        $file_path = $this->root_path.'/wa-config/config.php';
        if (file_exists($file_path)) {
            $config = include($file_path);
            foreach ($config as $name => $value) {
                self::$system_options[$name] = $value;
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
            return include($path);
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
            return $this->getPath('apps').'/'.$app.'/lib/config/'.$name;
        }
    }

    public function getRouting()
    {
        return $this->getConfigFile('routing');
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

    public function setAuth($data)
    {
        $path = $this->getPath('config', 'auth');
        if (waUtils::varExportToFile($data, $path)) {
            $cache = new waRuntimeCache('wa-config/auth');
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

}