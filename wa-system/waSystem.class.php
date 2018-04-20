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
            $app_name = method_exists($config, 'getApplication') ? $config->getApplication() : '';
            waLog::log('Error initializing waSystem('.$app_name.'): '.$e->getMessage()."\n".wa_dump_helper($config));
            echo $e;
        }
    }

    /**
     * Returns instance of configuration management class (waSystemConfig or waAppConfig).
     *
     * @return  SystemConfig|waAppConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns an instance of the main system class.
     * Short method of obtaining the same result is using function wa().
     *
     * @see wa()
     *
     * @param  string          $name         Id of app whose configuration must be temporarily initialized instead
     *     of current app's configuration.
     * @param  waSystemConfig  $config
     * @param  bool            $set_current
     * @throws  waException
     * @return  waSystem  Instance of waSystem class
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
            if ($config === null && self::$current && !empty(self::$instances[self::$current])) {
                /**
                 * @var $system waSystem
                 */
                $system = self::$instances[self::$current];
                $locale = $set_current ? $system->getLocale() : null;
                if (self::$apps === null || !empty(self::$apps[$name])) {
                    $config = SystemConfig::getAppConfig($name, $system->getEnv(), $system->config->getRootPath(), $locale);
                }
            }
            if ($config) {
                self::$instances[$name] = new self($config);
                if (!self::$instances[$name] instanceof waSystem) {
                    throw new waException(sprintf('Class "%s" is not of the type waSystem.', $config));
                }
                if ($config instanceof waAppConfig) {
                    $config->checkUpdates();
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
            /** @var $s waSystem */
            $s = self::$instances[$name];

            // Load application locale. Make sure it's an app,
            // since waSystemConfig does not have locale-related methods.
            if ($s->getConfig() instanceof waAppConfig) {
                $s->getConfig()->setLocale($s->getLocale());
            }
        }
    }

    public static function isLoaded($app_id)
    {
        return isset(self::$instances[$app_id]);
    }

    public function loadFactories()
    {
        if (self::$current == 'wa-system') {
            $file_path = $this->getConfig()->getPath('config', 'factories');
            if (file_exists($file_path)) {
                self::$factories_config = include($file_path);
            }
        }
        if (isset(self::$factories_config['locale'])) {
            waLocale::init(self::getCommonFactory('locale', 'waLocaleAdapter'));
        } else {
            waLocale::init();
        }
    }

    /**
     * @return waFrontController
     */
    public function getFrontController()
    {
        return $this->getFactory('front_controller', 'waFrontController', array());
    }

    public function getDefaultController()
    {
        return $this->getFactory('default_controller', 'waDefaultViewController');
    }

    /**
     * Returns instance of class used for generation of web pages (template engine).
     * If not overridden in individual app configuration, instance of class waSmarty3View is used.
     *
     * @param  array  $options  Array of parameters for initialization of the template engine class instance.
     * @return waSmarty3View|waView
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
     * Returns instance of class used for routing managing (waRouting).
     *
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
     * @return object
     */
    protected function getFactory($name, $class, $options = array(), $first_param = false)
    {
        if (isset($this->factories[$name])) {
            return $this->factories[$name];
        }
        if (($config = $this->getConfig()->getFactory($name))) {
            if (is_array($config)) {
                $class = $config[0];
                $options = isset($config[1]) ? $config[1] : $options;
            } else {
                $class = $config;
            }
        }
        if (!class_exists($class)) {
            throw new waException('Unable to load factory class '.$class);
        }
        if ($first_param !== false) {
            $this->factories[$name] = new $class($first_param, $options);
        } else {
            $this->factories[$name] = new $class($options);
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
    protected static function getCommonFactory($name, $class, $options = array(), $first_param = false)
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
     * @param string $type
     * @param string $app_id
     * @return waCache
     */
    public function getCache($type = 'default', $app_id = null)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        if ($app_id != $this->getConfig()->getApplication()) {
            return wa($app_id)->getCache($type);
        }
        return $this->getConfig()->getCache($type);
    }

    /**
     * @param waUser $user
     */
    public function setUser(waUser $user)
    {
        if (!waConfig::get('is_template')) {
            self::$factories_common['auth_user'] = $user;
        }
    }

    /**
     * Returns instance of class used for accessing user-related information.
     *
     * @return  waAuthUser|waUser|waContact
     */
    public function getUser()
    {
        return $this->getCommonFactory('auth_user', 'waAuthUser', array(), null);
    }


    /**
     * @param string $adapter
     * @return waMapAdapter
     * @throws waException
     */
    public function getMap($adapter = null)
    {
        if (empty($adapter)) {
            $adapter = wa()->getSetting('map_adapter', 'google', 'webasyst');
        }

        $file = $this->config->getPath('system').'/map/adapters/'.$adapter.'Map.class.php';
        if (!file_exists($file)) {
            $file = $this->config->getPath('plugins').'/map/adapters/'.$adapter.'Map.class.php';
        }
        if (file_exists($file)) {
            require_once($file);
            $class = $adapter.'Map';
            if (class_exists($class)) {
                return new $class();
            }
        }
        throw new waException(sprintf("Map adapter %s not found.", var_export($adapter, true)));
    }

    /**
     * @return waMapAdapter[]
     */
    public function getMapAdapters()
    {
        $locale = $this->getLocale();
        $result = array();
        $paths = array(
            $this->config->getPath('system').'/map/adapters/',
            $this->config->getPath('plugins').'/map/adapters/',
        );
        foreach ($paths as $path) {
            foreach (waFiles::listdir($path) as $f) {
                try {
                    if (substr($f, -13) == 'Map.class.php') {
                        $adapter = substr($f, 0, -13);
                        $class = $adapter.'Map';
                        require_once($path.$f);
                        if (class_exists($class)) {
                            $obj = new $class();
                            /**
                             * @var waMapAdapter $obj
                             */
                            if ($obj->getLocale() && !in_array($locale, $obj->getLocale())) {
                                continue;
                            }
                            $result[$adapter] = $obj;
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
        return $result;
    }


    /**
     * Returns auth adapter.
     *
     * @param string $provider
     * @param array $params
     * @return waiAuth|waAuthAdapter
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
     * Returns instance of class used for managing user sessions (waSessionStorage).
     *
     * @return  waSessionStorage
     */
    public function getStorage()
    {
        return $this->getCommonFactory('storage', 'waSessionStorage');
    }


    /**
     * Returns instance of class used for accessing user requests (waRequest).
     *
     * @return waRequest
     */
    public function getRequest()
    {
        return $this->getCommonFactory('request', 'waRequest', array(), $this);
    }

    /**
     * Returns instance of class used for generating response to user requests (waResponse).
     *
     * @return  waResponse
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

    /**
     * Determines the type of user request environment: 'backend' or 'frontend'.
     *
     * @return  string
     */
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
            if (waConfig::get('is_template')) {
                return;
            }

            // Request URL with no '?' and GET parameters
            $request_url = $this->config->getRequestUrl(true, true);
            if ($this->getEnv() == 'backend') {
                $this->dispatchBackend($request_url);
            } else {
                $this->dispatchFrontend($request_url);
            }

        } catch(Exception $e) {
            if (!waSystemConfig::isDebug() && !in_array($e->getCode(), array(404, 403))) {
                $log = array("Uncaught exception ".get_class($e).":");
                $log[] = $e->getMessage()." (".$e->getCode().")";
                $log[] = $e instanceof waException ? $e->getFullTraceAsString() : $e->getTraceAsString();
                waLog::log(join("\n", $log));
            }
            if (class_exists('waException')) {
                if (!$e instanceof waException) {
                    $e = new waException($e->getMessage(), $e->getCode(), $e);
                }
                $e->sendResponseCode();
            }
            print $e;
        }
    }

    private function dispatchBackend($request_url)
    {
        // Redirect to HTTPS if set up in domain params
        if (!waRequest::isHttps() && waRouting::getDomainConfig('ssl_all')) {
            $domain = $this->getRouting()->getDomain(null, true);
            $url = 'https://'.$this->getRouting()->getDomainUrl($domain).'/'.$this->getConfig()->getRequestUrl();
            $this->getResponse()->redirect($url, 301);
            return;
        }

        // Publicly available dashboard?
        $url = explode("/", $request_url);
        if (ifset($url[1]) == 'dashboard') {
            wa('webasyst', 1)->getFrontController()->execute(null, 'dashboard', 'tv');
            return;
        }

        // Access to backend without being logged in as is_user > 0
        // -> show login form
        if (!$this->getUser()->isAuth()) {
            wa('webasyst', 1)->getFrontController()->execute(null, 'login', waRequest::get('action'), true);
            return;
        }

        // Determine active application
        $url = explode("/", $request_url);
        $app = isset($url[1]) && ($url[1] != 'index.php') ? $url[1] : 'webasyst';
        if (!$app) {
            $app = 'webasyst';
        }

        if (!$this->appExists($app)) {
            if (wa('webasyst', 1)->event('backend_dispatch_miss', $app)) {
                return;
            }
            throw new waException("Page not found", 404);
        }

        // Make sure user has access to active app
        if ($app != 'webasyst' && !$this->getUser()->getRights($app, 'backend')) {
            throw new waRightsException('Access to this app denied', 403);
        }

        // Init system and app
        wa('webasyst');
        $wa_app = wa($app, 1);

        // Check CSRF protection token if current active app enabled it
        if ($wa_app->getConfig()->getInfo('csrf') && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        // Pass through to FrontController of an active app
        $wa_app->getFrontController()->dispatch();
    }

    private function dispatchFrontend($request_url)
    {
        // Sitemap?
        if (preg_match('/^sitemap-?([a-z0-9_]+)?(-([0-9]+))?.xml$/i', $request_url, $m)) {
            $app_id = isset($m[1]) ? $m[1] : 'webasyst';
            if ($this->appExists($app_id)) {
                wa($app_id, 1);
                $class = $app_id.'SitemapConfig';
                if (class_exists($class)) {
                    /** @var $sitemap waSitemapConfig */
                    $sitemap = new $class();
                    $sitemap->display(ifempty($m[3], 1));
                    return;
                }
            }

            throw new waException("Page not found", 404);
        }

        // Payment callback?
        if (!strncmp($request_url, 'payments.php/', 13)) {
            $url = substr($request_url, 13);
            if (preg_match('~^([a-z0-9_]+)~i', $url, $m) && !empty($m[1])) {
                $module_id = $m[1];
                waRequest::setParam('module_id', $module_id);
                wa('webasyst', 1)->getFrontController()->execute(null, 'payments');
            }
            return;
        }

        // Redirect to HTTPS if set up in domain params
        if (!waRequest::isHttps() && waRouting::getDomainConfig('ssl_all')) {
            $domain = $this->getRouting()->getDomain(null, true);
            $url = 'https://'.$this->getRouting()->getDomainUrl($domain).'/'.$this->getConfig()->getRequestUrl();
            $this->getResponse()->redirect($url, 301);
            return;
        }

        // Captcha?
        if (preg_match('/^([a-z0-9_]+)?\/?captcha\.php$/i', $request_url, $m)) {
            $app_id = isset($m[1]) ? $m[1] : 'webasyst';
            if ($this->appExists($app_id)) {
                $captcha = wa($app_id, 1)->getCaptcha(array('app_id' => $app_id));
                $captcha->display();
                return;
            }

            throw new waException("Page not found", 404);
        }

        // Oauth?
        if (!strncmp($request_url, 'oauth.php', 9)) {
            $app_id = $this->getStorage()->get('auth_app');
            if (!$app_id) {
                $app_id = waRequest::get('app', null, 'string');
            }
            if ($app_id && !$this->appExists($app_id)) {
                throw new waException("Page not found", 404);
            }
            $app_system = wa($app_id, 1);
            if (!class_exists($app_id.'OAuthController')) {
                $app_system = wa('webasyst', 1);
            }
            $app_system->getFrontController()->execute(null, 'OAuth');
            return;
        }

        // One-time auth app token?
        if (!strncmp($request_url, 'link.php/', 9)) {
            $token = strtok(substr($request_url, 9), '/?');
            if ($token) {
                $app_token_model = new waAppTokensModel();
                $row = $app_token_model->getById($token);
                if ($row) {
                    if ($row['expire_datetime'] && strtotime($row['expire_datetime']) < time()) {
                        $app_token_model->purge();
                    } else {
                        wa($row['app_id'], true)->getConfig()->dispatchAppToken($row);
                        return;
                    }
                }
            }

            throw new waException("Page not found", 404);
        }

        // Therefore, this is a regular frontend pageview.
        // Run it through the routing, redirecting to backend if no routing is set up.
        $route_found = $this->getRouting()->dispatch();
        if (!$route_found) {
            $this->getResponse()->redirect($this->getConfig()->getBackendUrl(true), 302);
            return;
        }

        // Redirect to HTTPS if set up in routing params
        if (!waRequest::isHttps() && waRequest::param('ssl_all')) {
            $domain = $this->getRouting()->getDomain(null, true);
            $url = 'https://'.$this->getRouting()->getDomainUrl($domain).'/'.$this->getConfig()->getRequestUrl();
            $this->getResponse()->redirect($url, 301);
            return;
        }

        // Active application determined by the routing
        $app = waRequest::param('app', null, 'string');
        if (!$app) {
            $app = 'webasyst';
        }

        // Is this a logout?
        $logout_url = waRequest::get('logout', null, 'string');
        if ($logout_url !== null) {
            $contact_id = $this->getUser()->getId();
            if ($contact_id) {
                // logout user
                $this->getUser()->logout();

                // logging logout
                if (!class_exists('waLogModel')) {
                    wa('webasyst');
                }
                $log_model = new waLogModel();
                $log_model->insert(array(
                    'app_id'     => $app,
                    'contact_id' => $contact_id,
                    'datetime'   => date("Y-m-d H:i:s"),
                    'params'     => 'frontend',
                    'action'     => 'logout',
                ));
            } else {
                // We destroy session even if user is not logged in.
                // This clears session-based pseudo-auth for many apps.
                wa()->getStorage()->destroy();
                // Do not allow custom URL in this case
                // because of redirection-based phishing attacks
                $logout_url = null;
            }

            // Make sure redirect is to the same domain
            if (!empty($logout_url)) {
                $domain = $this->getRouting()->getDomain(null, true);
                $next_domain = @parse_url($logout_url, PHP_URL_HOST);
                if ($next_domain && $domain !== $next_domain) {
                    $logout_url = null;
                }
            }
            // make redirect after logout
            if (empty($logout_url)) {
                $logout_url = $this->config->getRequestUrl(false, true);
            }
            $this->getResponse()->redirect($logout_url);
            return;
        }

        // Initialize active application
        $app_system = wa($app, 1);

        // Access to a secure area of the frontend without being logged in
        // -> show login form
        if (!$this->getUser()->isAuth() && (waRequest::param('secure') || waRequest::param('auth'))) {
            $auth = $this->getAuthConfig();
            if (!empty($auth['app'])) {
                $app_system = wa($auth['app'], 1);
            }
            $app_system->login();
            return;
        }

        // CSRF protection for secure parts of the frontend
        if (waRequest::param('secure') && waRequest::method() == 'post' && $app_system->getConfig()->getInfo('csrf')) {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        // All seems fine, pass the request to FrontController of an active app
        $app_system->getFrontController()->dispatch();
    }

    public function dispatchCli($argv)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $params = array();
        $app = $argv[1];
        $class = $app.ucfirst(ifset($argv[2], 'help'))."Cli";
        $argv = array_slice($argv, 3);
        while ($arg = array_shift($argv)) {
            if (mb_substr($arg, 0, 2) == '--') {
                $key = mb_substr($arg, 2);
            } elseif (mb_substr($arg, 0, 1) == '-') {
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

        if (!$this->appExists($app)) {
            throw new waException("App ".$app." not found", 404);
        }
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

    /**
     * Returns current user's locale.
     *
     * @return  string  E.g., 'en_US'.
     */
    public function getLocale()
    {
        if ($locale = waLocale::getLocale()) {
            return $locale;
        }
        return $this->getUser()->getLocale();
    }

    /**
     * Sets specified locale for framework's dynamic configuration.
     *
     * @param  string  $locale  Locale id; e.g., 'en_US'.
     */
    public function setLocale($locale)
    {
        $this->getConfig()->setLocale($locale);
    }

    /**
     * Returns version number for specified app.
     *
     * @param  string|null  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @return  string
     */
    public function getVersion($app_id = null)
    {
        if ($app_id === null) {
            if ($this->getConfig() instanceof waAppConfig) {
                $app_id = $this->getConfig()->getApplication();
            } else {
                $app_id = 'webasyst';
            }
        }

        $app_info = $this->getAppInfo($app_id);
        $version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        if (isset($app_info['build']) && $app_info['build']) {
            $version .= '.'.$app_info['build'];
        }
        return $version;
    }

    /**
     * Returns current app's id.
     *
     * @return string
     */
    public static function getApp()
    {
        if (self::$current != 'wa-system') {
            return self::$current;
            //return $this->getConfig()->getApplication();
        } else {
            return null;
        }
    }

    /**
     * Returns information about specified app from its configuration file wa-apps/[app_id]/lib/config/app.php.
     *
     * @param  string  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @return  array  App configuration data.
     */
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

    /**
     * Returns path to app's source files directory.
     *
     * @param  string|null  $path    Optional path to a subdirectory inside app's source files directory.
     * @param  string|null  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @return  string
     */
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
            $path = preg_replace('!\.\.[/\\\]!', '', $path);
        }
        $file = waConfig::get('wa_path_cache').'/apps/'.$app_id.($path ? '/'.$path : '');
        waFiles::create($path ? dirname($file) : $file);
        return $file;
    }

    public function getCachePath($path = null, $app_id = null)
    {
        return $this->getAppCachePath($path, $app_id);
    }

    /**
     * Returns path framework's directory used for storing custom configuration files.
     *
     * @param  string|null  $app_id  Id of the app for which the path to configuration file directory must be returned.
     *     If not specified, method returns path to common configuration files directory.
     * @return  string
     */
    public function getConfigPath($app_id = null)
    {
        $path = waConfig::get('wa_path_config');
        if ($app_id) {
            $path .= '/apps/'.$app_id;
        }
        return $path;
    }

    /**
     * Returns path to current app's data directory.
     *
     * @param  string|null  $path    Optional path to a subdirectory in main directory with user data files.
     * @param  bool         $public  Flag requiring to return path to the subdirectory used for storing files publicly
     *     accessible without authorization, by direct link. If 'false' (default value), then method returns path to
     *     subdirectory used for storing files accessible only upon authorization in the backend.
     * @param  string|null  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @param  bool         $create  Flag requiring to create a new directory directory at the specified path if it is
     *     missing. New directories are created by default if 'false' is not specified.
     * @return  string
     */
    public function getDataPath($path = null, $public = false, $app_id = null, $create = true)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        if ($path) {
            $path = preg_replace('!\.\.[/\\\]!', '', $path);
        }
        $file = waConfig::get('wa_path_data').'/'.($public ? 'public' : 'protected').'/'.$app_id.($path ? '/'.$path : '');
        if ($create) {
            waFiles::create($file);
        }
        return $file;
    }

    /**
     * Returns URL of directory used for storing user data files for specified app.
     *
     * @param  string|null   $path      Optional path to a subdirectory in main directory with user data files.
     * @param  bool          $public    Flag requiring to return path to the subdirectory used for storing files publicly
     *     accessible without authorization, by direct link. If 'false' (default value), then method returns path to
     *     subdirectory used for storing files accessible only upon authorization in the backend.
     * @param  string|null   $app_id    Optional app id. If not specified, then current app's id is used by default.
     * @param  bool          $absolute  Return absolute URL instead of the relative one (default value).
     * @return  string
     */
    public function getDataUrl($path = null, $public = false, $app_id = null, $absolute = false)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        $data_path = $this->getDataPath($path, $public, $app_id, false);
        $base = waConfig::get('wa_path_root');
        if (strpos($data_path, $base) === 0) {
            $data_path = substr($data_path, strlen($base) + 1);
        } else {
            $data_path = 'wa-data/'.($public ? 'public' : 'protected').'/'.$app_id.($path ? '/'.$path : '');
        }
        return $this->getRootUrl($absolute).$data_path;
    }

    /**
     * Returns path to directory used for storing app's temporary files.
     *
     * @param  string|null  $path    Optional path to a subdirectory in main temporary files directory.
     * @param  string|null  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @return  string
     */
    public function getTempPath($path = null, $app_id = null)
    {
        if ($app_id === null) {
            $app_id = $this->getConfig()->getApplication();
        }
        if ($path) {
            $path = preg_replace('!\.\.[/\\\]!', '', $path);
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
            if (!file_exists($file) || filemtime($file) < filemtime($this->getConfig()->getPath('config', 'apps'))) {
                waFiles::create($this->getConfig()->getPath('cache').'/config');
                $all_apps = include($this->getConfig()->getPath('config', 'apps'));
                $all_apps['webasyst'] = true;
                self::$apps = array();
                foreach ($all_apps as $app => $enabled) {
                    if ($enabled) {
                        $app_config = $this->getAppPath('lib/config/app.php', $app);
                        if (!file_exists($app_config)) {
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
                        waLocale::loadByDomain($app, $locale);
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
                        } elseif (isset($app_info['icon'][48])) {
                            $app_info['img'] = $app_info['icon'][48];
                        }
                        if (isset($app_info['img'])) {
                            if (!isset($app_info['icon'][48])) {
                                $app_info['icon'][48] = $app_info['img'];
                            }
                            if (!isset($app_info['icon'][24])) {
                                $app_info['icon'][24] = $app_info['icon'][48];
                            }
                            if (!isset($app_info['icon'][16])) {
                                $app_info['icon'][16] = $app_info['icon'][24];
                            }
                        }
                        self::$apps[$app] = array(
                            'id' => $app,
                        ) + $app_info;
                    }
                }
                if (!file_exists($file) || filemtime($file) < filemtime($this->getConfig()->getPath('config', 'apps'))) {
                    waUtils::varExportToFile(self::$apps, $file);
                }
            } else {
                self::$apps = include($file);
                waLocale::loadByDomain('webasyst', $locale);
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
                        if (!isset($domain_info)) {
                            $domain_model = new siteDomainModel();
                            $domain_info = $domain_model->getByName($domain);
                        }
                        $name = ($domain_info && $domain_info['title']) ? $domain_info['title'] : $this->accountName();
                    }
                } else {
                    $name = $all_apps[$r['app']]['name'];
                }
                $apps[] = array(
                    'url' => $path.'/'.$url,
                    'name' => $escape ? htmlspecialchars($name) : $name,
                    'app' => $r['app']
                );
            }
        }
        return array_reverse($apps);
    }

    /**
     * Returns account name saved in Installer settings.
     *
     * @return string
     */
    public function accountName()
    {
        $app_settings_model = new waAppSettingsModel();
        return $app_settings_model->get('webasyst', 'name', 'Webasyst');
    }

    /**
     * Verifies whether application with specified id exists.
     *
     * @param  string  $app_id  App id.
     * @return bool
     */
    public function appExists($app_id)
    {
        $this->getApps();
        return $app_id === 'webasyst' || isset(self::$apps[$app_id]);
    }

    /**
     * Returns the main URL of the current frontend or backend section.
     * If a request is sent to frontend, then method return framework's root frontend URL.
     * If a request is sent to backend, then method returns main backend URL of the app responsible for processing request.
     *
     * @param  bool  $absolute  Flag requiring to return the absolute URL instead of the relative one (default value).
     * @return  string
     */
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

    /**
     * Returns URL corresponding to specified combination of app's module and action based on the contents of
     * configuration file routing.php of specified app.
     *
     * @param string  $path      App, module, and action IDs separated by slash /
     * @param array   $params    Associative array of the following optional parameters:
     *     - 'domain': domain name specified for one of existing websites
     *     - 'module': module id
     *     - 'action': action id
     *     - dynamic URL parameters described in app configuration file routing.php for specified module and action;
     *         e.g., 'category_url' is such a dynamic parameter in the following routing configuration entry:
     *         'category/<category_url>/' => 'frontend/category',
     * @param  bool    $absolute  Flag requiring to return an absolute URL instead of a relative one.
     * @param string $domain
     * @param string $route
     * @return string
     */
    public function getRouteUrl($path, $params = array(), $absolute = false, $domain = null, $route = null)
    {
        return $this->getRouting()->getUrl($path, $params, $absolute, $domain, $route);
    }

    /**
     * Returns relative URL of specified app's main backend page.
     *
     * @param  string|null  $app     Optional app id. If not specified, then current app's id is used by default.
     * @param  bool         $script  Flag requiring to return a URL containing index.php/ in cases when module.
     *     mod_rewrite (or similar mechanism) for generating human-readable URLs is not available.
     * @return  string
     */
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

    /**
     * Returns URL of specified app's source files directory.
     *
     * @param  string|null  $app       Optional app id. If not specified, then current app's id is used by default.
     * @param  bool         $absolute  Flag requiring to return an absolute URL instead of a relative one (default value).
     * @return  string
     */
    public function getAppStaticUrl($app = null, $absolute = false)
    {
        if (!$app || $app === true) {
            $app = $this->getApp();
        }
        $url = $this->config->getRootUrl($absolute);
        return $url.'wa-apps/'.$app.'/';
    }

    /**
     * Returns the root URL of framework installation directory.
     *
     * @param  bool  $absolute  Flag requiring to return the absolute URL instead of the relative one (default value).
     * @param  bool  $script    Flag requiring to return a URL containing index.php/ when module mod_rewrite (or
     *     similar mechanism) for generating human-readable URLs is not installed
     * @return  string
     */
    public function getRootUrl($absolute = false, $script = false)
    {
        return $this->config->getRootUrl($absolute, $script);
    }

    /**
     * Returns a setting value for specified app.
     *
     * @param  string       $name     Settings field string id.
     * @param  mixed        $default  Default value, which is returned if requested settings field contains no value.
     * @param  string|null  $app_id   Optional app id. If not specified, then current app's id is used by default.
     * @return  mixed
     */
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
     *
     * @param  string  $app    Id of app which has generated specified event.
     * @param  string  $event  Event name.
     * @return  array  List of arrays ['className', 'method', 'pluginId', 'appId'].
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
     * Returns waPlugin object by plugin id.
     *
     * @param  string  $plugin_id
     * @param bool $set_active
     * @throws  waException
     * @return  waPlugin
     */
    public function getPlugin($plugin_id, $set_active = false)
    {
        $app_id = $this->getConfig()->getApplication();
        $path = $this->getConfig()->getPluginPath($plugin_id).'/lib/config/plugin.php';
        if (file_exists($path)) {
            $class = $app_id.ucfirst($plugin_id).'Plugin';
            if (!class_exists($class)) {
                throw new waException('Plugin class '.$class.' '.$plugin_id.' not found');
            }
            $plugin_info = include($path);
            $plugin_info['id'] = $plugin_id;
            if (isset($plugin_info['img'])) {
                $plugin_info['img'] = 'wa-apps/'.$app_id.'/plugins/'.$plugin_id.'/'.$plugin_info['img'];
            }
            if (!isset($plugin_info['app_id'])) {
                $plugin_info['app_id'] = $app_id;
            }
            $build_file = $this->getConfig()->getPluginPath($plugin_id).'lib/config/build.php';
            if (file_exists($build_file)) {
                $plugin_info['build'] = include($build_file);
            } else {
                if (SystemConfig::isDebug()) {
                    $plugin_info['build'] = time();
                } else {
                    $plugin_info['build'] = 0;
                }
            }
            // load locale
            $locale_path = $this->getAppPath('plugins/'.$plugin_id.'/locale', $app_id);
            if (is_dir($locale_path)) {
                waLocale::load($this->getLocale(), $locale_path, $app_id.'_'.$plugin_id, false);
            }
            if ($set_active) {
                self::pushActivePlugin($plugin_id, $app_id);
            }
            return new $class($plugin_info);
        } else {
            throw new waException('Plugin '.$plugin_id.' @ '.$app_id.' not found');
        }
    }

    /**
     * @param $widget_id
     * @return waWidget
     * @throws waException
     */
    public function getWidget($widget_id)
    {
        $widget_model = new waWidgetModel();
        $widget = $widget_model->getById($widget_id);
        if ($widget) {
            if ($this->getConfig()->getApplication() != $widget['app_id']) {
                $path = self::getInstance($widget['app_id'])->getConfig()->getWidgetPath($widget['widget']);
            } else {
                $path = $this->getConfig()->getWidgetPath($widget['widget']);
            }
            $widget_path = $path . '/lib/config/widget.php';
            if (file_exists($widget_path)) {
                if ($widget['app_id'] == 'webasyst') {
                    $class_filename = $path . '/lib/' . $widget['widget'] . '.widget.php';
                    if (file_exists($class_filename)) {
                        require_once($class_filename);
                    } else {
                        throw new waException('Widget '.$widget['widget'].' not found', 404);
                    }
                    $class = $widget['widget'].'Widget';
                } else {
                    $class = $widget['app_id'] . ucfirst($widget['widget']) . 'Widget';
                }
                if (!class_exists($class)) {
                    throw new waException('Widget class '.$class.' '.$widget['widget'].' not found', 404);
                }
                $widget_config = include($widget_path);
                $widget = $widget + $widget_config;
                if (isset($widget['img'])) {
                    $widget['img'] = 'wa-apps/'.$widget['app_id'].'/widgets/'.$widget['widget'].'/'.$widget['img'];
                }
                return new $class($widget);
            } else {
                throw new waException('Widget '.$widget['widget'].' not found', 404);
            }
        } else {
            throw new waException('Widget '.$widget_id.' not found', 404);
        }
    }

    /**
     * Trigger event with given $name from current active application.
     *
     * @param  string|array  $name        Event name or array('app id', 'event name').
     * @param  mixed         $params      Parameters passed to event handlers.
     * @param  string[]      $array_keys  Array of expected template items for UI events.
     * @return  array  app_id or plugin_id => data returned from handler (unless null is returned)
     */
    public function event($name, &$params = null, $array_keys = null)
    {
        $plugins = null;
        $result = array();
        $event_system = $this;
        if (is_array($name)) {
            $event_app_id = $name[0];
            $name = $name[1];
            if ($this->appExists($event_app_id)) {
                $event_system = self::getInstance($event_app_id);
            } else {
                $event_system = self::getInstance('webasyst');
                $plugins = array();
            }
        } else {
            $event_app_id = $this->getConfig()->getApplication();
        }

        // Super event hook in wa-config/SystemConfig is called for all events
        if (method_exists($event_system->getConfig(), 'eventHook')) {
            $r = $event_system->getConfig()->eventHook($event_app_id, $name, $params);
            if (is_array($r)) {
                return $r;
            }
        }

        // Make sure active app stays the same after the event
        $old_app = self::getApp();

        // Load event handlers list from apps
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

        // Load event handlers list from plugins
        if (!isset(self::$handlers['plugins'][$event_app_id])) {
            if ($plugins === null) {
                $plugins = $event_system->getConfig()->getPlugins();
            }
            self::$handlers['plugins'][$event_app_id] = array();
            foreach ($plugins as $plugin_id => $plugin) {
                if (!empty($plugin['handlers'])) {
                    foreach ($plugin['handlers'] as $handler_event => $handler_method) {
                        if (!is_array($handler_method)) {
                            if ($handler_event && $handler_event{0} != '/') {
                                self::$handlers['plugins'][$event_app_id][$handler_event][$plugin_id] = $handler_method;
                            } else {
                                // Allows plugins to ask for all events based on regex match, e.g. '/backend_.*/'
                                self::$handlers['plugins'][$event_app_id]['*'][$plugin_id][$handler_event] = $handler_method;
                            }
                        }
                    }
                }
            }

        }

        // Trigger event handlers of apps
        if (isset(self::$handlers['apps'][$event_app_id][$name])) {
            $path = $this->getConfig()->getPath('apps');
            foreach (self::$handlers['apps'][$event_app_id][$name] as $app_id) {
                $file_path = $path.'/'.$app_id.'/lib/handlers/'.$event_app_id.".".$name.".handler.php";
                if (!file_exists($file_path) || !wa()->appExists($app_id)) {
                    continue;
                }
                wa($app_id);
                include_once($file_path);
                $class_name = $name;
                if (strpos($name, '.') !== false) {
                    $class_name = strtok($class_name, '.').ucfirst(strtok(''));
                }
                $class_name = $app_id.ucfirst($event_app_id).ucfirst($class_name)."Handler";
                if (!class_exists($class_name)) {
                    if (waSystemConfig::isDebug()) {
                        waLog::log('Event handler class does not exist: '.$class_name);
                    }
                    continue;
                }

                /**
                 * @var $handler waEventHandler
                 */
                self::pushActivePlugin(null, $app_id);
                try {
                    $handler = new $class_name();
                    $r = $handler->execute($params);
                    if ($r !== null) {
                        $result[$app_id] = $r;
                    }
                } catch (Exception $e) {
                    waLog::log('Event handling error in '.$file_path.': '.$e->getMessage());
                }
                self::popActivePlugin();
            }
        }

        //
        // Trigger event handlers of plugins
        //

        // Each plugin can only be called once per event.
        // (Except several regex-based wildcard matches when
        // there are no higher-priority handlers defined for the event.)
        $plugins_called = array();

        // Try plugin handlers: literal match first, then wildcards.
        $aliases = array($name);
        if (strpos($name, '.') !== false) {
            $aliases[] = preg_replace('@\.[^\.]+$@', '.*', $name);
        }
        $aliases[] = '*';
        foreach ($aliases as $alias) {
            if (!isset(self::$handlers['plugins'][$event_app_id][$alias])) {
                continue;
            }
            if ($plugins === null) {
                $plugins = $event_system->getConfig()->getPlugins();
            }
            foreach (self::$handlers['plugins'][$event_app_id][$alias] as $plugin_id => $methods) {
                if (!isset($plugins[$plugin_id]) || !empty($plugins_called[$plugin_id])) {
                    continue;
                }
                $plugins_called[$plugin_id] = true;
                $class_name = $event_app_id.ucfirst($plugin_id).'Plugin';
                if (!class_exists($class_name)) {
                    if (waSystemConfig::isDebug()) {
                        waLog::log('Event handler class does not exist: '.$class_name);
                    }
                    continue;
                }

                // Make sure there is at least one plugin method that passes a regex test.
                // This optimizes away expensive waLocale::load() call for wildcard-based handlers.
                // (There may be several methods when plugin subscribed for several regex wildcards.)
                if (!is_array($methods)) {
                    $methods = array('' => $methods);
                }
                foreach($methods as $regex => $method) {
                    if ($regex != '' && !preg_match($regex, $name)) {
                        unset($methods[$regex]);
                    }
                }
                if (!$methods) {
                    continue;
                }

                // Activate _wp() for current plugin
                self::pushActivePlugin($plugin_id, $event_app_id);

                // Load plugin locale
                $locale_path = $this->getAppPath('plugins/'.$plugin_id.'/locale', $event_app_id);
                if (is_dir($locale_path)) {
                    waLocale::load($this->getLocale(), $locale_path, self::getActiveLocaleDomain(), false);
                }

                // Call plugin handler methods that match the event.
                try {
                    $class = new $class_name($plugins[$plugin_id]);
                    foreach($methods as $regex => $method) {
                        if (!method_exists($class, $method)) {
                            if (waSystemConfig::isDebug()) {
                                waLog::log('Event handler method does not exist: '.$class_name.'->'.$method.'()');
                            }
                            continue;
                        }
                        if ($name == $alias) {
                            $r = $class->$method($params);
                        } else {
                            $r = $class->$method($params, $name);
                        }
                        if ($r !== null) {
                            if ($array_keys && is_array($r)) {
                                foreach ($array_keys as $k) {
                                    if (!isset($r[$k])) {
                                        $r[$k] = '';
                                    }
                                }
                            }
                            $result[$plugin_id.'-plugin'] = $r;

                            // Only one result can be returned per event per plugin.
                            // So we ignore all other methods matched by regex wildcard
                            // after we get a result.
                            break;
                        }
                    }
                } catch (Exception $e) {
                    waLog::log('Event handling error in '.$class_name.":\n".$e->getMessage()."\n".$e->getTraceAsString());
                }

                // Deactivate _wp() for current plugin
                self::popActivePlugin();
            }
        }

        wa($old_app, 1);

        // Super event hook in wa-config/SystemConfig is called for all events
        if (method_exists($event_system->getConfig(), 'eventHookAfter')) {
            $r = $event_system->getConfig()->eventHookAfter($event_app_id, $name, $params, $result);
            if (is_array($r)) {
                return $r;
            }
        }

        return $result;
    }

    /**
     * Returns list of app themes.
     *
     * @param  string  $app_id  Optional app id. If not specified, then current app's id is used by default.
     * @param  string  $domain
     * @return  waTheme[]
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
        foreach ($theme_ids as $id) {
            $theme = new waTheme($id, $app_id);
            if ($theme->path) {
                $themes[$id] = $theme;
            }
            unset($theme);
        }
        return $themes;
    }
}

/**
 * Convenient form of waSystem::getInstance()
 *
 * @param  string  $name
 * @param  bool    $set_current
 * @return  waSystem
 */
function wa($name = null, $set_current = false)
{
    return waSystem::getInstance($name, null, $set_current);
}
