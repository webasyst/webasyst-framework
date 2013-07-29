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
 */
class waSystem
{
    protected static $instances = array();
    protected static $current = 'wa-system';

    protected static $apps;
    protected static $handlers = array();
    protected static $factories_common = array();
    protected static $factories_config = array();

    protected static $models = array();

    protected $url;

    /**
     * @var SystemConfig|waAppConfig
     */
    protected $config;
    protected $factories = array();

    protected function __construct(waSystemConfig $config)
    {
        $this->config = $config;
        try {
            $this->loadFactories();
        } catch (Exception $e) {
            echo $e;
        }

    }

    public static function isLoaded()
    {
        return self::$instances !== array();
    }

    /**
     * @return SystemConfig|waAppConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $name
     * @param waSystemConfig $config
     * @param bool $set_current
     * @throws waException
     * @return waSystem
     */
    public static function getInstance($name = null, waSystemConfig $config = null, $set_current = false)
    {
        if ($name === null) {
            if ($config && $config instanceof waAppConfig) {
                $name = $config->getName();
            } else {
                $name = self::$current;
            }
        }

        if (!isset(self::$instances[$name])) {
            if ($config === null && self::$current) {
                /**
                 * @var $system waSystem
                 */
                $system = self::$instances[self::$current];
                $locale = $set_current ? $system->getLocale() : null;
                $config = SystemConfig::getAppConfig($name, $system->getEnv(), $system->config->getRootPath(), $locale);
            }
            if ($config) {
                self::$instances[$name] = new self($config);
                if (!self::$instances[$name] instanceof waSystem) {
                    throw new waException(sprintf('Class "%s" is not of the type waSystem.', $config));
                }
            } else {
                throw new waException(sprintf('The "%s" system does not exist.', $name));
            }
        }
        if ($set_current) {
            self::setActive($name);
        } elseif (!self::$current || self::$current == 'wa-system') {
            self::$current = $name;
        }
        return self::$instances[$name];
    }

    public static function setActive($name)
    {
        if (isset(self::$instances[$name])) {
            self::$current = $name;
            /**
             * @var $s waSystem
             */
            $s = self::$instances[$name];
            $s->getConfig()->setLocale($s->getLocale());
        }
    }

    public function loadFactories()
    {
        if (self::$current == 'wa-system') {
            $file_path = $this->getConfig()->getPath('config', 'factories');
            if (file_exists($file_path)) {
                self::$factories_config = include($file_path);
            }
        }
        waLocale::init();
    }

    /**
     * @return waFrontController
     */
    public function getFrontController()
    {
        return $this->getFactory('front_controller', 'waFrontController', array());
    }

    /**
     * @param array $options
     * @return waSmarty3View
     */
    public function getView($options = array())
    {
        return $this->getFactory('view', 'waSmarty3View', $options, $this);
    }

    /**
     * @param array $options
     * @return waCaptcha
     */
    public function getCaptcha($options = array())
    {
        return $this->getFactory('captcha', 'waCaptcha', $options);
    }

    /**
     * @return waRouting
     */
    public function getRouting()
    {
        return $this->getCommonFactory('routing', 'waRouting', array(), self::getInstance('wa-system'));
    }

    /**
     * @param string $name
     * @param string $class
     * @param array $options
     * @param mixed $first_param
     * @return mixed
     */
    public function getFactory($name, $class, $options = array(), $first_param = false)
    {
        if ($config = $this->getConfig()->getFactory($name)) {
            if (is_array($config)) {
                $class = $config[0];
                $options = isset($config[1]) ? $config[1] : $options;
            } else {
                $class = $config;
            }
        }
        if (!isset($this->factories[$name])) {
            if ($first_param !== false) {
                $this->factories[$name] = new $class($first_param, $options);
            } else {
                $this->factories[$name] = new $class($options);
            }
        }
        return $this->factories[$name];
    }

    /**
     * @param string $name
     * @param string $class
     * @param array $options
     * @param mixed $first_param
     * @return mixed
     */
    public static function getCommonFactory($name, $class, $options = array(), $first_param = false)
    {
        if (!isset(self::$factories_common[$name])) {
            if (isset(self::$factories_config[$name])) {
                $config = self::$factories_config[$name];
                if (is_array($config) && isset($config[0])) {
                    $class = $config[0];
                    $options = isset($config[1]) ? $config[1] : $options;
                } else {
                    $class = $config;
                }
            }
            if ($first_param !== false) {
                self::$factories_common[$name] = new $class($first_param, $options);
            } else {
                self::$factories_common[$name] = new $class($options);
            }
        }
        return self::$factories_common[$name];
    }

    /**
     * @param string $name
     * @param object $value
     */
    public function setCommonFactory($name, $value)
    {
        self::$factories_common[$name] = $value;
    }

    /**
     * @return waAuthUser|waUser|waContact
     */
    public function getUser()
    {
        return $this->getCommonFactory('auth_user', 'waAuthUser', array(), null);
    }

    /**
     * Returns auth adapter
     *
     * @param string $provider
     * @param array $params
     * @return waAuth
     * @throws waException
     */
    public function getAuth($provider = null, $params = array())
    {
        if ($provider) {
            $file = $this->config->getPath('system').'/auth/adapters/'.$provider.'Auth.class.php';
            if (!file_exists($file)) {
                $file = $this->config->getPath('plugins').'/auth/adapters/'.$provider.'Auth.class.php';
            }
            if (file_exists($file)) {
                require_once($file);
                $class = $provider.'Auth';
                return new $class($params);
            } else {
                throw new waException("Auth provider not found.");
            }
        } else {
            $options = array();
            if (isset(self::$factories_config['auth'])) {
                $config = self::$factories_config['auth'];
                if (is_array($config) && isset($config[0])) {
                    $class = $config[0];
                    $options = isset($config[1]) ? $config[1] : $options;
                } else {
                    $class = $config;
                }
            } else {
                $class = 'waAuth';
            }
            return $this->getFactory('auth', $class, $options);
        }
    }

    public function getAuthAdapters($domain = null)
    {
        $config = $this->getAuthConfig($domain);
        $result = array();
        if (!empty($config['adapters'])) {
            foreach ($config['adapters'] as $provider => $params) {
                if ($params) {
                    $result[$provider] = $this->getAuth($provider, $params);
                }
            }
        }
        return $result;
    }

    public function getAuthConfig($domain = null)
    {
        if (!$domain) {
            $domain = $this->getRouting()->getDomain(null, true);
        }
        $config = $this->getConfig()->getAuth();
        if (!isset($config[$domain])) {
            return array();
        }
        return $config[$domain];
    }


    /**
     * @return waSessionStorage
     */
    public function getStorage()
    {
        return $this->getCommonFactory('storage', 'waSessionStorage');
    }


    /**
     * @return waRequest
     */
    public function getRequest()
    {
        return $this->getCommonFactory('request', 'waRequest', array(), $this);
    }

    /**
     * @return waResponse
     */
    public function getResponse()
    {
        return $this->getCommonFactory('response', 'waResponse');
    }

    /**
     * @return waDateTime
     */
    public function getDateTime()
    {
        return $this->getCommonFactory('datetime', 'waDateTime', array(), $this);
    }

    public function getEnv()
    {
        return $this->config->getEnvironment();
    }

    public function login()
    {
        $prefix = $this->getConfig()->getPrefix().'Login';
        if (class_exists($prefix.'Controller') || class_exists($prefix.'Action')) {
            $this->getFrontController()->execute(null, 'login');
        } else {
            // load webasyst
            self::getInstance('webasyst');
            $controller = new webasystLoginController();
            $controller->run();
        }
    }

    public function dispatch()
    {
        try {
            if (preg_match('/^sitemap-?([a-z0-9_]+)?.xml$/i', $this->config->getRequestUrl(true), $m)) {
                $app_id = isset($m[1]) ? $m[1] : 'webasyst';
                if ($this->appExists($app_id)) {
                    self::getInstance($app_id);
                    $class = $app_id.'SitemapConfig';
                    if (class_exists($class)) {
                        /**
                         * @var $sitemap waSitemapConfig
                         */
                        $sitemap = new $class();
                        $sitemap->display();
                    }
                } else {
                    throw new waException("Page not found", 404);
                }
            } elseif (preg_match('/^([a-z0-9_]+)?\/?captcha\.php$/i', $this->config->getRequestUrl(true, true), $m)) {
                $app_id = isset($m[1]) ? $m[1] : 'webasyst';
                if ($this->appExists($app_id)) {
                    $wa = self::getInstance($app_id, null, true);
                    $captcha = $wa->getCaptcha(array('app_id' => $app_id));
                    $captcha->display();
                } else {
                    throw new waException("Page not found", 404);
                }
            } elseif (!strncmp($this->config->getRequestUrl(true), 'oauth.php', 9)) {
                $app_id = $this->getStorage()->get('auth_app', 'webasyst');
                $app_system = self::getInstance($app_id);
                if (class_exists($app_id.'OAuthController')) {
                    $app_system->getFrontController()->execute(null, 'OAuth');
                } else {
                    wa('webasyst')->getFrontController()->execute(null, 'OAuth');
                }
            } elseif (!strncmp($this->config->getRequestUrl(true), 'payments.php/', 13)) {
                $url = substr($this->config->getRequestUrl(true), 13);
                waRequest::setParam('module_id', strtok($url, '/?'));
                $webasyst_system = self::getInstance('webasyst');
                $webasyst_system->getFrontController()->execute(null, 'payments', null, true);
            } elseif ($this->getEnv() == 'backend' && !$this->getUser()->isAuth()) {
                $webasyst_system = self::getInstance('webasyst', null, true);
                $webasyst_system->getFrontController()->execute(null, 'login', waRequest::get('action'), true);
            } elseif ($this->config instanceof waAppConfig) {
                if ($this->getEnv() == 'backend' && !$this->getUser()->getRights($this->getConfig()->getApplication(), 'backend')) {
                    header("Location: ".$this->getConfig()->getBackendUrl(true));
                    exit;
                }
                $this->getFrontController()->dispatch();
            } else {
                $app = null;
                $route = null;
                if ($this->getEnv() == 'frontend') {
                    // logout
                    if (null !== ( $logout_url = waRequest::get('logout'))) {
                        $this->getAuth()->clearAuth();
                        if (!$logout_url) {
                            $logout_url = $this->config->getRequestUrl(false, true);
                        }
                        $this->getResponse()->redirect($logout_url);
                    }
                    if (!$this->getRouting()->dispatch()) {
                        $routes = $this->getRouting()->getRoutes();
                        $redirect = true;
                        $route = end($routes);
                        if (isset($route['app'])) {
                                $redirect = false;
                                // set routing
                                foreach ($route as $k => $v) {
                                    if ($k !== 'url') {
                                        waRequest::setParam($k, $v);
                                    }
                                }
                                waRequest::setParam('error', 404);
                        }
                        if ($redirect) {
                            $this->getResponse()->redirect($this->getConfig()->getBackendUrl(true), 302);
                        }
                    }
                    $app = waRequest::param('app');
                } else {
                    self::getInstance('webasyst');
                    $path = $this->getConfig()->getRequestUrl(true);
                    if (($i = strpos($path, '?')) !== false) {
                        $path = substr($path, 0, $i);
                    }
                    $url = explode("/", $path);
                    $app = isset($url[1]) && ($url[1] != 'index.php') ? $url[1] : 'webasyst';
                }
                if (!$app) {
                    $app = 'webasyst';
                }

                $app_system = self::getInstance($app, null, true);
                if ($app != 'webasyst' && $this->getEnv() == 'backend' && !$this->getUser()->getRights($app_system->getConfig()->getApplication(), 'backend')) {
                    //$this->getResponse()->redirect($this->getConfig()->getBackendUrl(true), 302);
                    throw new waRightsException('Access to this app denied', 403);
                }
                if ((waRequest::param('secure') || waRequest::param('auth')) && !$this->getUser()->isAuth()) {
                    $app_system->login();
                } else {
                    $app_system->getFrontController()->dispatch();
                }
            }
        } catch(waApiException $e) {
            print $e;
        } catch(waException $e) {
            print $e;
        } catch(Exception $e) {
            if (waSystemConfig::isDebug()) {
                print $e;
            } else {
                $e = new waException($e->getMessage(), $e->getCode());
                print $e;
            }
        }
    }

    public function dispatchCli($argv)
    {
        $params = array();
        $app = $argv[1];
        $class = $app.ucfirst($argv[2])."Cli";
        $argv = array_slice($argv, 3);
        while ($arg = array_shift($argv)) {
            if(mb_substr($arg, 0, 2) == '--') {
                $key = mb_substr($arg, 2);
            } else if(mb_substr($arg, 0, 1) == '-') {
                $key = mb_substr($arg, 1);
            } else {
                $params[] = $arg;
                continue;
            }
            $params[$key] = trim(array_shift($argv));
        }
        waRequest::setParam($params);
        // Load system
        waSystem::getInstance('webasyst');
        // Load app
        waSystem::getInstance($app, null, true);
        if (class_exists($class)) {
            /**
             * @var $cli waCliController
             */
            $cli = new $class();
            $cli->run();
        } else {
            throw new waException("Class ".$class." not found", 404);
        }
    }

    public function getLocale()
    {
        if ($locale = waLocale::getLocale()) {
            return $locale;
        }
        return $this->getUser()->getLocale();
    }

    public function setLocale($locale)
    {
        $this->getConfig()->setLocale($locale);
    }

    public function getVersion($app_id = null)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }

        $app_info = $this->getAppInfo($app_id);
        $version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        if (isset($app_info['build']) && $app_info['build']) {
            $version .= '.'.$app_info['build'];
        }
        return $version;
    }

    public static function getApp()
    {
        if (self::$current != 'wa-system') {
            return self::$current;
            //return $this->getConfig()->getApplication();
        } else {
            return null;
        }
    }

    public function getAppInfo($app_id = null)
    {
        if ($app_id === null) {
            $app_id = $this->getApp();
        }
        if ($this->appExists($app_id)) {
            return self::$apps[$app_id];
        }
        return null;
    }

    public function getAppPath($path = null, $app_id = null)
    {
        if ($app_id === null) {
            if ($this->getConfig() instanceof waAppConfig) {
                $app_id = $this->getConfig()->getApplication();
            } else {
                $app_id = 'webasyst';
            }
        }
        return waConfig::get($app_id == 'webasyst' ? 'wa_path_system' : 'wa_path_apps').'/'.$app_id.($path ? '/'.$path : '');
    }

    public function getAppCachePath($path = null, $app_id = null)
    {
        if ($app_id === null) {
            if ($this->getConfig() instanceof waAppConfig) {
                $app_id = $this->getConfig()->getApplication();
            } else {
                $app_id = 'webasyst';
            }
        }
        if ($path) {
            $path = preg_replace('!\.\.[/\\\]!','', $path);
        }
        $file = waConfig::get('wa_path_cache').'/apps/'.$app_id.($path ? '/'.$path : '');
        waFiles::create($path ? dirname($file) : $file);
        return $file;
    }

    public function getCachePath($path = null, $app_id = null)
    {
        return $this->getAppCachePath($path, $app_id);
    }

    public function getConfigPath($app_id = null)
    {
        $path = waConfig::get('wa_path_config');
        if ($app_id) {
            $path .= '/apps/'.$app_id;
        }
        return $path;
    }


    /**
     *
     * Return path to data directory of the current application
     *
     * @param string $path - relative path in data dir
     * @param bool $public - public or protected dir
     * @param string $app_id
     * @param bool $create
     * @return string
     */
    public function getDataPath($path = null, $public = false, $app_id = null, $create = true)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        if ($path) {
            $path = preg_replace('!\.\.[/\\\]!','', $path);
        }
        $file = waConfig::get('wa_path_data').'/'.($public ? 'public' : 'protected').'/'.$app_id.($path ? '/'.$path : '');
        if ($create) {
            waFiles::create($file);
        }
        return $file;
    }

    public function getDataUrl($path = null, $public = false, $app_id = null, $absolute = false)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        return $this->getRootUrl($absolute).'wa-data/'.($public ? 'public' : 'protected').'/'.$app_id.($path ? '/'.$path : '');
    }


    /**
     * Return path in temp directory of the current application
     *
     * @param string $path - relative path
     * @param string $app_id
     * @return string
     */
    public function getTempPath($path = null, $app_id = null)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        if ($path) {
            $path = preg_replace('!\.\.[/\\\]!','', $path);
        }
        $dir = waConfig::get('wa_path_cache').'/temp/'.$app_id.($path ? '/'.$path : '');
        waFiles::create($dir);
        return $dir;
    }

    public function getApps($system = false)
    {
        if (self::$apps === null) {
            $locale = $this->getUser()->getLocale();
            $file = $this->config->getPath('cache', 'config/apps'.$locale);
            if (!file_exists($this->getConfig()->getPath('config', 'apps'))) {
                self::$apps = array();
                throw new waException('File wa-config/apps.php not found.', 600);
            }
            if (!file_exists($file) || filemtime($file) < filemtime($this->getConfig()->getPath('config', 'apps')) || waSystemConfig::isDebug()) {
                waFiles::create($this->getConfig()->getPath('cache').'/config');
                $all_apps = include($this->getConfig()->getPath('config', 'apps'));
                $all_apps['webasyst'] = true;
                self::$apps = array();
                foreach ($all_apps as $app => $enabled) {
                    if ($enabled) {
                        waLocale::loadByDomain($app, $locale);
                        $app_config = $this->getAppPath('lib/config/app.php', $app);
                        if (!file_exists($app_config)) {
                            if (false && SystemConfig::isDebug()) {
                                throw new waException("Config not found. Create config by path ".$app_config);
                            }
                            continue;
                        }
                        $app_info = include($app_config);
                        $build_file = $app_config = $this->getAppPath('lib/config/build.php', $app);
                        if (file_exists($build_file)) {
                            $app_info['build'] = include($build_file);
                        } else {
                            if (SystemConfig::isDebug()) {
                                $app_info['build'] = time();
                            } else {
                                $app_info['build'] = 0;
                            }
                        }
                        $app_info['id'] = $app;
                        $app_info['name'] = _wd($app, $app_info['name']);
                        if (isset($app_info['icon'])) {
                            if (is_array($app_info['icon'])) {
                                foreach ($app_info['icon'] as $size => $url) {
                                    $app_info['icon'][$size] = 'wa-apps/'.$app.'/'.$url;
                                }
                            } else {
                                $app_info['icon'] = array(
                                    48 => 'wa-apps/'.$app.'/'.$app_info['icon']
                                );
                            }
                        } else {
                            $app_info['icon'] = array();
                        }
                        if (isset($app_info['img'])) {
                            $app_info['img'] = 'wa-apps/'.$app.'/'.$app_info['img'];
                        } else {
                            $app_info['img'] = isset($app_info['icon'][48]) ? $app_info['icon'][48] : 'wa-apps/'.$app.'/img/'.$app.".png";
                        }
                        if (!isset($app_info['icon'][48])) {
                            $app_info['icon'][48] = $app_info['img'];
                        }
                        if (!isset($app_info['icon'][24])) {
                            $app_info['icon'][24] = $app_info['icon'][48];
                        }
                        if (!isset($app_info['icon'][16])) {
                            $app_info['icon'][16] = $app_info['icon'][24];
                        }
                        self::$apps[$app] = $app_info;
                    }
                }
                if (!file_exists($file) || filemtime($file) < filemtime($this->getConfig()->getPath('config', 'apps'))) {
                    waUtils::varExportToFile(self::$apps, $file);
                }
            } else {
                self::$apps = include($file);
                waLocale::loadByDomain('webasyst');
            }
        }
        if ($system) {
            return self::$apps;
        } else {
            $apps = self::$apps;
            unset($apps['webasyst']);
            return $apps;
        }
    }

    /**
     * @param $domain
     * @param string $domain_name
     * @param bool $escape
     * @return array
     */
    public function getFrontendApps($domain, $domain_name = null, $escape = false)
    {
        $routes = $this->getRouting()->getRoutes($domain);
        $path = waRouting::getDomainUrl($domain, false);

        $apps = array();
        $all_apps = $this->getApps();
        foreach ($routes as $r) {
            if (isset($r['app']) && isset($all_apps[$r['app']])) {
                if (!empty($r['private'])) {
                    continue;
                }
                $url = $r['url'];
                $url = waRouting::clearUrl($url);
                if (strpos($url, '<') !== false) {
                    continue;
                }
                if (isset($r['_name'])) {
                    $name = $r['_name'];
                } elseif ($r['app'] == 'site') {
                    if ($domain_name) {
                        $name = $domain_name;
                    } else {
                        if (!isset(self::$instances['site'])) {
                            self::getInstance('site');
                        }
                        $domain_model = new siteDomainModel();
                        $domain_info = $domain_model->getByName($domain);
                        $name = ($domain_info && $domain_info['title']) ? $domain_info['title'] : $this->accountName();
                    }
                } else {
                    $name = $all_apps[$r['app']]['name'];
                }
                $apps[] = array(
                    'url' => $path.'/'.$url,
                    'name' => $escape ? htmlspecialchars($name) : $name
                );
            }
        }
        return array_reverse($apps);
    }

    public function accountName()
    {
        $app_settings_model = new waAppSettingsModel();
        return $app_settings_model->get('webasyst', 'name', 'Webasyst');
    }

    public function appExists($app_id)
    {
        $this->getApps();
        return $app_id === 'webasyst' || isset(self::$apps[$app_id]);
    }

    public function getUrl($absolute = false)
    {
        $url = $this->config->getRootUrl($absolute);
        if ($this->config->getEnvironment() == 'backend' && ($app = $this->getApp())) {
            $url .= $this->config->getBackendUrl().'/';
            if ($app !== 'webasyst') {
                $url .= $app.'/';
            }
        }
        return $url;
    }

    public function getRouteUrl($path, $params = array(), $absolute = false)
    {
        return $this->getRouting()->getUrl($path, $params, $absolute);
    }

    public function getAppUrl($app = null, $script = false)
    {
        if ($app === null) {
            $app = $this->getApp();
        }
        if ($this->getEnv() == 'backend') {
            $url = $this->config->getRootUrl();
            if ($app == 'webasyst') {
                return $url.$this->getConfig()->getBackendUrl()."/";
            } else {
                return $url.$this->getConfig()->getBackendUrl()."/".$app."/";
            }
        } else {
            $url = $this->config->getRootUrl(false, $script);
            return $url.$this->getRouting()->getRootUrl();
        }
    }

    public function getAppStaticUrl($app = null, $absolute = false)
    {
        if (!$app || $app === true) {
            $app = $this->getApp();
        }
        $url = $this->config->getRootUrl($absolute);
        return $url.'wa-apps/'.$app.'/';
    }

    public function getRootUrl($absolute = false, $script = false)
    {
        return $this->config->getRootUrl($absolute, $script);
    }

    public static function getSetting($name, $default = '', $app_id = null)
    {
        if ($app_id === null) {
            $app_id = self::getApp();
        }
        if (!isset(self::$models['app_settings'])) {
            self::$models['app_settings'] = new waAppSettingsModel();
        }
        return self::$models['app_settings']->get($app_id, $name, $default);
    }

    /** Active plugin for _wp(). Updated by wa()->event(). */
    protected static $activePlugin = array();

    public static function getActiveLocaleDomain()
    {
        if (self::$activePlugin) {
            return implode('_', end(self::$activePlugin));
        } else {
            return null;
        }
    }

    public static function pushActivePlugin($plugin, $app = null)
    {
        if (!$app) {
            $app = wa()->getConfig()->getPrefix();
        }
        return array_push(self::$activePlugin, $plugin ? array($app, $plugin) : array($app));
    }

    public static function popActivePlugin()
    {
        return array_pop(self::$activePlugin);
    }

    /**
     * Return all handlers bound to event $event generated by $app.
     * Currently only checks $app's own plugins, but this may be changed in future.
     * @param string $app application id that generated event
     * @param string $event event name
     * @return array list of arrays ['className', 'method', 'pluginId', 'appId']
     */
    protected function getPlugins($app, $event)
    {
        //$system = self::getInstance($app);
        $plugins = $this->getConfig()->getPlugins();
        $result = array();
        foreach ($plugins as $plugin_id => $plugin) {
            foreach ($plugin['handlers'] as $handler_event => $handler_method) {
                if ($event == $handler_event) {
                    $class = $app.ucfirst($plugin_id).'Plugin';
                    $result[] = array($class, $handler_method, $plugin_id, $app);
                }
            }
        }
        return $result;
    }

    /**
     * Returns waPlugin object by plugin id
     *
     * @param string $plugin_id
     * @return waPlugin
     * @throws waException
     */
    public function getPlugin($plugin_id)
    {
        $app_id = $this->getConfig()->getApplication();
        $path = $this->getConfig()->getPluginPath($plugin_id).'/lib/config/plugin.php';
        if (file_exists($path)) {
            $class = $app_id.ucfirst($plugin_id).'Plugin';
            $plugin_info = include($path);
            $plugin_info['id'] = $plugin_id;
            if (isset($plugin_info['img'])) {
                $plugin_info['img'] = 'wa-apps/'.$app_id.'/plugins/'.$plugin_id.'/'.$plugin_info['img'];
            }
            if (!isset($plugin_info['app_id'])) {
                $plugin_info['app_id'] = $app_id;
            }
            // load locale
            self::pushActivePlugin($plugin_id, $app_id);
            $locale_path = $this->getAppPath('plugins/'.$plugin_id.'/locale', $app_id);
            if (is_dir($locale_path)) {
                waLocale::load($this->getLocale(), $locale_path, self::getActiveLocaleDomain(), false);
            }
            return new $class($plugin_info);
        } else {
            throw new waException('Plugin '.$plugin_id.' not found');
        }
    }

    /**
     * Trigger event with given $name from current active application.
     * @param string $name
     * @param mixed $params passed to event handlers
     * @return array app_id or plugin_id => data returned from handler (unless null is returned)
     */
    public function event($name, &$params = null)
    {
        $result = array();
        if (is_array($name)) {
            $event_app_id = $name[0];
            $event_system = self::getInstance($event_app_id);
            $name = $name[1];
        } else {
            $event_app_id = $this->getConfig()->getApplication();
            $event_system = $this;
        }
        $event_prefix = wa($event_app_id)->getConfig()->getPrefix();

        if (!isset(self::$handlers['apps'])) {
            self::$handlers['apps'] = array();
            $cache_file = $this->config->getPath('cache', 'config/handlers');
            if (!waSystemConfig::isDebug() && file_exists($cache_file)) {
                self::$handlers['apps'] = include($cache_file);
            }
            if (!self::$handlers['apps'] || !is_array(self::$handlers['apps'])) {
                $apps = $this->getApps();
                $path = $this->getConfig()->getPath('apps');
                foreach ($apps as $app_id => $app_info) {
                    $files = waFiles::listdir($path.'/'.$app_id.'/lib/handlers/');
                    foreach ($files as $file) {
                        if (substr($file, -12) == '.handler.php') {
                            $file = explode('.', substr($file, 0, -12), 2);
                            self::$handlers['apps'][$file[0]][$file[1]][] = $app_id;
                        }
                    }
                }
                if (!waSystemConfig::isDebug()) {
                    waUtils::varExportToFile(self::$handlers['apps'], $cache_file);
                }
            }
        }

        if (!isset(self::$handlers['plugins'][$event_app_id])) {
            self::$handlers['plugins'][$event_app_id] = array();
            $plugins = $event_system->getConfig()->getPlugins();
            foreach ($plugins as $plugin_id => $plugin) {
                if (!empty($plugin['handlers'])) {
                    foreach ($plugin['handlers'] as $handler_event => $handler_method) {
                        self::$handlers['plugins'][$event_app_id][$handler_event][$plugin_id] = $handler_method;
                    }
                }
            }
        }

        if (isset(self::$handlers['apps'][$event_app_id][$name])) {
            $path = $this->getConfig()->getPath('apps');
            foreach (self::$handlers['apps'][$event_app_id][$name] as $app_id) {
                $file_path = $path.'/'.$app_id.'/lib/handlers/'.$event_prefix.".".$name.".handler.php";
                if (!file_exists($file_path)) {
                    continue;
                }
                wa($app_id);
                include($file_path);
                $class_name = $name;
                if (strpos($name, '.') !== false) {
                    $class_name = strtok($class_name, '.').ucfirst(strtok(''));
                }
                $class_name = $app_id.ucfirst($event_prefix).ucfirst($class_name)."Handler";
                /**
                 * @var $handler waEventHandler
                 */
                $handler = new $class_name();
                try {
                    $r = $handler->execute($params);
                    if ($r !== null) {
                        $result[$app_id] = $r;
                    }
                } catch (Exception $e) {
                    waLog::log('Event handling error in '.$file_path.': '.$e->getMessage());
                }
            }
        }
        if (isset(self::$handlers['plugins'][$event_app_id][$name])) {
            $plugins = $event_system->getConfig()->getPlugins();
            foreach (self::$handlers['plugins'][$event_app_id][$name] as $plugin_id => $method) {
                if (!isset($plugins[$plugin_id])) {
                    continue;
                }
                $plugin = $plugins[$plugin_id];
                self::pushActivePlugin($plugin_id, $event_prefix);
                $class_name = $event_app_id.ucfirst($plugin_id).'Plugin';
                try {
                    $class = new $class_name($plugin);
                    // Load plugin locale if it exists
                    $locale_path = $this->getAppPath('plugins/'.$plugin_id.'/locale', $event_app_id);
                    if (is_dir($locale_path)) {
                        waLocale::load($this->getLocale(), $locale_path, self::getActiveLocaleDomain(), false);
                    }
                    if (method_exists($class, $method) && null !== ( $r = $class->$method($params))) {
                        $result[$plugin_id.'-plugin'] = $r;
                    }
                } catch (Exception $e) {
                    waLog::log('Event handling error in '.$class_name.'->'.$name.'(): '.$e->getMessage());
                }
                self::popActivePlugin();
            }
        }
        return $result;
    }

    /**
     * Return list of application themes
     * @param string $app_id default is current application
     * @param string $app_id optional to get
     * @return array
     */
    public function getThemes($app_id = null, $domain = null)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }

        $theme_paths = array(
            'original' => $this->getAppPath('themes', $app_id),
            'custom'   => $this->getDataPath('themes', true, $app_id, false),
        );

        $theme_ids = array();
        foreach ($theme_paths as $path) {
            if (file_exists($path) && is_dir($path) && ($dir = opendir($path))) {
                while ($current = readdir($dir)) {
                    if ($current !== '.' && $current !== '..' &&
                        is_dir($path.'/'.$current) && file_exists($path.'/'.$current.'/theme.xml')) {
                        $theme_ids[] = $current;
                    }
                }
                closedir($dir);
            }
        }

        $themes = array();
        array_unique($theme_ids);
        foreach($theme_ids as $id) {
            $theme = new waTheme($id,$app_id);
            if ($theme->path) {
                $themes[$id] = $theme;
            }
            unset($theme);
        }
        return $themes;
    }
}

/**
 * Alias for waSystem::getInstance()
 * @param string $name
 * @return waSystem
 */
function wa($name = null)
{
    return waSystem::getInstance($name);
}