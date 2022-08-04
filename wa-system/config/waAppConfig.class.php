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

class waAppConfig extends SystemConfig
{
    protected $application = null;
    protected $info = array();
    protected $log_actions = null;
    protected $system_log_actions = null;
    protected $prefix;
    protected $plugins = null;
    protected $widgets = null;
    protected $themes = null;
    protected $options = array();
    protected $routes = null;
    protected $loaded_locale = null;

    public function __construct($environment, $root_path, $application = null, $locale = null)
    {
        if ($application) {
            $this->application = $application;
        } else {
            $this->application = substr(get_class($this), 0, -6);
        }
        parent::__construct($environment, $root_path);
        if ($locale) {
            $this->setLocale($locale);
        }
    }

    /**
     * Returns app's id.
     *
     * @return string
     */
    public function getApplication()
    {
        return $this->application;
    }

    public function getLogActions($full = false, $ignore_system = false)
    {
        if ($this->log_actions === null) {
            $path = $this->getAppPath().'/lib/config/logs.php';
            if (file_exists($path)) {
                $this->log_actions = include($path);
                if ($full) {
                    foreach ($this->log_actions as &$info) {
                        if (!empty($info['name'])) {
                            $info['name'] = _wd($this->getApplication(), $info['name']);
                        }
                    }
                    unset($info);
                }
            } else {
                $this->log_actions = array();
            }
        }
        if (!$ignore_system) {
            $system_actions = $this->getSystemLogActions();
            return array_merge($this->log_actions, $system_actions);
        }
        return $this->log_actions;
    }

    public function explainLogs($logs)
    {
        $page_ids = array();
        foreach ($logs as $l_id => $l) {
            if (in_array($l['action'], array('page_add', 'page_edit', 'page_move'))) {
                $page_ids[] = $l['params'];
            } elseif (substr($l['action'], 0, 8) == 'template' || substr($l['action'], 0, 5) == 'theme') {
                $logs[$l_id]['params_html'] = htmlspecialchars($l['params']);
            }
        }
        if ($page_ids) {
            $class_name = $this->application.'PageModel';
            if (class_exists($class_name)) {
                /** @var waPageModel $model */
                $model = new $class_name();
                $pages = $model->query('SELECT id, name FROM '.$model->getTableName().' WHERE id IN (i:ids)', array('ids' => $page_ids))->fetchAll('id', true);
                $app_url = wa()->getConfig()->getBackendUrl(true).$this->application.'/';
                foreach ($logs as &$l) {
                    if (in_array($l['action'], array('page_add', 'page_edit', 'page_move')) && isset($pages[$l['params']])) {
                        $l['params_html'] = '<div class="activity-target"><a href="'.$app_url.'#/pages/'.$l['params'].'">'.
                            htmlspecialchars($pages[$l['params']]).'</a></div>';
                    }
                }
                unset($l);
            }
        }
        return $logs;
    }

    public function getSystemLogActions()
    {
        if ($this->system_log_actions === null) {
            $actions = array();
            // add system actions for design and pages
            if (!empty($this->info['themes'])) {
                $actions = array_merge($actions, array(
                    'template_add'    => array(
                        'name' => _ws('added a new template')
                    ),
                    'template_edit'   => array(
                        'name' => _ws('edited template')
                    ),
                    'template_delete' => array(
                        'name' => _ws('deleted template')
                    ),
                    'theme_upload'    => array(
                        'name' => _ws('uploaded a new theme')
                    ),
                    'theme_download'  => array(
                        'name' => _ws('downloaded theme')
                    ),
                    'theme_delete'    => array(
                        'name' => _ws('deleted theme')
                    ),
                    'theme_reset'     => array(
                        'name' => _ws('reset theme settings')
                    ),
                    'theme_duplicate' => array(
                        'name' => _ws('create theme duplicate')
                    ),
                    'theme_rename'    => array(
                        'name' => _ws('renamed theme')
                    ),
                ));
            }
            if (!empty($this->info['pages'])) {
                $actions = array_merge($actions, array(
                    'page_add'    => array(
                        'name' => _ws('added a new page')
                    ),
                    'page_edit'   => array(
                        'name' => _ws('edited a website page')
                    ),
                    'page_delete' => array(
                        'name' => _ws('deleted page')
                    ),
                    'page_move'   => array(
                        'name' => _ws('moved page')
                    )
                ));
            }
            $actions['login'] = array(
                'name' => _ws('logged in')
            );
            $actions['logout'] = array(
                'name' => _ws('logged out')
            );
            $actions['waid_auth'] = array(
                'name' => _ws('logged in with Webasyst ID')
            );
            $actions['login_failed'] = array(
                'name' => _ws('login failed')
            );
            $actions['signup'] = array(
                'name' => _ws('signed up')
            );
            $actions['my_profile_edit'] = array(
                'name' => _ws('edited profile in customer portal')
            );
            $actions['access_enable'] = array(
                'name' => _ws('enabled access for contact')
            );
            $actions['access_disable'] = array(
                'name' => _ws('disabled access for contact')
            );
            $actions['welcome'] = array(
                'name' => _ws('Webasyst is ready for work! Use the available apps via the icons at the page top and add more apps via Installer. Invite teammates and friends for collaboration via the Team app.')
            );
            $this->system_log_actions = $actions;
        }
        return $this->system_log_actions;
    }

    protected function configure()
    {

    }

    /**
     * Returns app configuration parameter values.
     *
     * @param string $name The name of configuration parameter whose value must be returned.
     * @return mixed
     */
    public function getOption($name = null)
    {
        if (!$name) {
            return $this->options;
        }
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function init()
    {
        $files = array(
            $this->getAppPath().'/lib/config/config.php', // defaults
            $this->getPath('config').'/apps/'.$this->application.'/config.php' // custom
        );
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                $config = include($file_path);
                if ($config && is_array($config)) {
                    foreach ($config as $name => $value) {
                        $this->options[$name] = $value;
                    }
                }
            }
        }

        $this->info = include($this->getAppPath().'/lib/config/app.php');
        waAutoload::getInstance()->add($this->getClasses());

        if (!empty($this->info['payment_plugins'])) {
            waAutoload::getInstance()->add(waPayment::getClasses());
        }

        if (!empty($this->info['shipping_plugins'])) {
            waAutoload::getInstance()->add(waShipping::getClasses());
        }

        if (file_exists($this->getAppPath().'/lib/config/factories.php')) {
            $this->factories = include($this->getAppPath().'/lib/config/factories.php');
        }
        if (!empty($this->options['factories']) && is_array($this->options['factories'])) {
            foreach ($this->options['factories'] as $k => $v) {
                $this->factories[$k] = $v;
            }
        }
    }

    public function checkUpdates()
    {
        $is_from_template = waConfig::get('is_template');
        $disable_exception_log = waConfig::get('disable_exception_log');
        waConfig::set('disable_exception_log', true);
        try {
            $app_settings_model = new waAppSettingsModel();
        } catch (waDbException $e) {
            // 1146 = Table doesn't exist
            if ($e->getCode() == 1146 && $this->application == 'webasyst') {
                // First launch of the framework with working db.php setup.
                // $this->install() call below will create wa_* tables, then we'll be able
                // to create and use models.
            } elseif ($e->getCode() == 1146 && $this->application != 'webasyst' && $this->environment == 'frontend') {
                // 'webasyst' system app is not automatically started in frontend (as opposed to backend).
                // When framework is first launched via frontend, wa_* tables do not exist yet.
                // So we launch the app to give it a chance to install properly.
                waConfig::set('disable_exception_log', $disable_exception_log);
                $this->initWebasystApp();
                $app_settings_model = new waAppSettingsModel();
            } else {
                waLog::log($e->real_message."\n".$e->getFullTraceAsString(), 'db.log');
                throw $e;
            }
        } catch (waException $e) {
            if ($e->getCode() == 600 && $this->application == 'webasyst') {
                // wa-config/db.php file does not exist.
                // This is part of initial configuration of the framework.
                return;
            } else {
                throw $e;
            }
        } finally {
            waConfig::set('disable_exception_log', $disable_exception_log);
        }
        if (!empty($app_settings_model)) {
            $time = $app_settings_model->get($this->application, 'update_time', null);
        }

        // Install the app and remember to skip all updates
        // if this is the first launch.
        $is_first_launch = false;
        if (empty($time)) {
            $time = null;
            $is_first_launch = true;

            waConfig::set('disable_exception_log', true);
            waConfig::set('is_template', null);
            try {
                $this->install();
            } catch (waException $e) {
                waLog::log("Error installing application ".$this->application." at first run:\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                throw $e;
            } finally {
                waConfig::set('disable_exception_log', $disable_exception_log);
                waConfig::set('is_template', $is_from_template);
            }

            if (empty($app_settings_model)) {
                $app_settings_model = new waAppSettingsModel();
            }
        }

        // Use cache to skip slow filesystem-based scanning for updates
        if (!self::isDebug()) {
            $cache = new waVarExportCache('updates', -1, $this->application);
            if ($time && $cache->isCached() && $cache->get() <= $time) {
                return;
            }
        }

        // Scan for app updates
        $files = $this->getUpdateFiles($this->getAppPath().'/lib/updates', $time);
        if ($files) {
            $keys = array_keys($files);
            $last_update_ts = end($keys);
        } else {
            $last_update_ts = 1;
        }

        // Remember last update file in cache
        if (!empty($cache)) {
            $cache->set($last_update_ts);
        }

        if (empty($app_settings_model)) {
            $app_settings_model = new waAppSettingsModel();
        }

        if ($is_first_launch) {
            // Updates are all skipped on app's first launch with install.php
            $app_settings_model->set($this->application, 'update_time', $last_update_ts);
        } elseif ($files) {
            if (!$this->loaded_locale) {
                // Force load locale
                $this->setLocale(wa()->getLocale());
            }
            waConfig::set('disable_exception_log', true);
            waConfig::set('is_template', null);
            $cache_database_dir = $this->getPath('cache').'/db';
            try {
                foreach ($files as $t => $file) {
                    try {

                        if (waSystemConfig::isDebug()) {
                            waLog::dump(sprintf('Try include file %s by app %s', $file, $this->application), 'meta_update.log');
                        }

                        $this->includeUpdate($file);
                        waFiles::delete($cache_database_dir, true);
                        $app_settings_model->set($this->application, 'update_time', $t);
                    } catch (Exception $e) {
                        waLog::log("Error running update of ".$this->application.": {$file}\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                        throw new waException(sprintf(_ws('Error while running update of %s app: %s'), $this->application, $file), 500, $e);
                    }
                }
            } finally {
                waConfig::set('disable_exception_log', $disable_exception_log);
                waConfig::set('is_template', $is_from_template);
            }
        }

    }

    protected function initWebasystApp()
    {
        wa('webasyst');
    }

    protected function getUpdateFiles($path, $time)
    {
        if (!file_exists($path)) {
            return array();
        }

        $files = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $filename = $file->getFilename();
            if (preg_match('/^[0-9]+\.php$/', $filename)) {
                $t = substr($filename, 0, -4);
                if ($t > $time) {
                    $files[$t] = $file->getPathname();
                }
            }
        }
        ksort($files);
        return $files;
    }

    private function includeUpdate($file)
    {
        include($file);
    }

    public function install()
    {
        // Create database scheme
        $file_db = $this->getAppPath('lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            $model->createSchema($schema);
        }

        // Mark localization files as recently changed.
        // This forces use of PHP localization adapter that does not get stuck in apache cache.
        $locale_path = $this->getAppPath('locale');
        if (file_exists($locale_path)) {
            $all_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($locale_path));
            $po_files = new RegexIterator($all_files, '~(\.po)$~i');
            foreach ($po_files as $f) {
                @touch($f->getPathname());
            }
        }

        // Installation script of the app
        $file = $this->getAppConfigPath('install');
        if (file_exists($file)) {
            if (!$this->loaded_locale) {
                // Force load locale
                $this->setLocale(wa()->getLocale());
            }
            $app_id = $this->application;
            /** @var string $app_id */
            include($file);
        }
    }

    public function uninstall()
    {
        // check uninstall.php
        $file = $this->getAppConfigPath('uninstall');
        if (file_exists($file)) {
            include($file);
        }

        $file_db = $this->getAppPath('lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            foreach ($schema as $table => $fields) {
                $sql = "DROP TABLE IF EXISTS ".$table;
                $model->exec($sql);
            }
        }
        // Remove all app settings
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->del($this->application);
        $contact_settings_model = new waContactSettingsModel();
        $contact_settings_model->deleteByField('app_id', $this->application);
        // Remove app tokens
        $app_tokens_model = new waAppTokensModel();
        $app_tokens_model->deleteByField('app_id', $this->application);
        // Remove all rights to app
        $contact_rights_model = new waContactRightsModel();
        $contact_rights_model->deleteByField('app_id', $this->application);
        // Remove logs
        $log_model = new waLogModel();
        $log_model->deleteByField('app_id', $this->application);
        // Remove cache
        waFiles::delete($this->getPath('cache').'/apps/'.$this->application);
    }

    public function setLocale($locale, $bind = true)
    {
        if ($this->loaded_locale != $locale) {
            if (preg_match('~[^a-zA-Z0-9\-\_\.]~', $locale)) {
                return;
            }
            $this->loaded_locale = $locale;
            waLocale::load($locale, $this->getAppPath('locale'), $this->application, $bind);
        }
        if ($bind && waLocale::getDomain() != $this->application) {
            waLocale::load($locale, $this->getAppPath('locale'), $this->application, $bind);
        }
    }

    public static function clearAutoloadCache($app_id)
    {
        $cache_file = waConfig::get('wa_path_cache').'/apps/'.$app_id.'/config/autoload.php';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }

    public function getClasses()
    {
        $cache_file = waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config/autoload.php';

        $result = null;

        if (!self::isDebug() && file_exists($cache_file)) {
            $result = @include($cache_file);
            if (!is_array($result)) {
                $result = null;
            }
        }

        if ($result === null) {
            waFiles::create(waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config');
            $paths = array($this->getAppPath().'/lib/');
            // plugins
            $all_plugins = waFiles::listdir($this->getAppPath().'/plugins');
            foreach ($all_plugins as $plugin_id) {
                $path = $this->getPluginPath($plugin_id).'/lib/';
                if (file_exists($path)) {
                    $paths[] = $path;
                }
            }
            // widgets
            $all_widgets = waFiles::listdir($this->getAppPath().'/widgets');

            foreach ($all_widgets as $w_id) {
                $path = $this->getWidgetPath($w_id).'/lib/';
                if (file_exists($path)) {
                    $paths[] = $path;
                }
            }
            // api
            if (file_exists($this->getAppPath().'/api')) {
                $v = waRequest::request('v', 1, 'int');
                if (file_exists($this->getAppPath().'/api/v'.$v)) {
                    $paths[] = $this->getAppPath().'/api/v'.$v.'/';
                }
            }

            $result = array();
            $length = strlen($this->getRootPath());
            foreach ($paths as $path) {
                $files = $this->getPHPFiles($path);
                foreach ($files as $file) {
                    if (strpos($file, '/lib/config/data/')) {
                        continue;
                    }
                    $class = $this->getClassByFilename(basename($file));
                    if ($class) {
                        // Classes in dir named /custom/ have priority.
                        // This allows to override code without modifications to the original.
                        if (isset($result[$class]) && false !== stripos(str_replace('\\', '/', $result[$class]), '/custom/')) {
                            continue;
                        }
                        $result[$class] = substr($file, $length + 1);
                    }
                }
            }

            if (!self::isDebug()) {
                waUtils::varExportToFile($result, $cache_file);
            } else {
                waFiles::delete($cache_file);
            }

        }

        return $result;
    }

    protected function getPHPFiles($path)
    {
        if (!($dh = opendir($path))) {
            throw new waException('Filed to open dir: '.$path);
        }
        $result = array();
        while (($f = readdir($dh)) !== false) {
            if ($this->isIgnoreFile($f)) {
                continue;
            } elseif (is_dir($path.$f)) {
                if (substr($path.$f, -12) == '/lib/updates') {
                    continue;
                }
                $result = array_merge($result, $this->getPHPFiles($path.$f.'/'));
            } elseif (substr($f, -4) == '.php') {
                $result[] = $path.$f;
            }
        }
        closedir($dh);
        return $result;
    }

    protected function isIgnoreFile($f)
    {
        return $f === '.' || $f === '..' || $f === '.svn' || $f === '.git';
    }

    protected function getClassByFilename($filename)
    {
        $class = waAutoload::getInstance()->getClassByFilename($filename, $this->application);
        return $class;
    }

    /**
     * Returns path to app's source files directory.
     *
     * @param string $path Optional path to a subdirectory inside app's lib/ directory
     * @return string
     */
    public function getAppPath($path = null)
    {
        return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-apps'.DIRECTORY_SEPARATOR.$this->application.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Returns path to specified configuration file of current app, located in its lib/config/ directory.
     *
     * @param string $name Name of configuration file without extension
     * @return string
     */
    public function getAppConfigPath($name)
    {
        return $this->getAppPath("lib/config/".$name.".php");
    }

    /**
     * Returns path to app's configuration file with specified name.
     *
     * @see waSystemConfig::getConfigPath()
     * @param string $name Name of the configuration file whose path must be returned
     * @param bool $user_config Whether path to a file located in wa-config/apps/[app_id]/ directory must be returned,
     *     which is used for storing custom user configuration. If false is specified, method returns path to a file
     *     located in wa-apps/[app_id]/lib/config/.
     * @param string $app Optional app id, defaults to current app's id
     * @return string
     */
    public function getConfigPath($name, $user_config = true, $app = null)
    {
        if ($app === null) {
            $app = $this->application;
        }
        return parent::getConfigPath($name, $user_config, $app);
    }

    public function getRouting($route = array())
    {
        if ($this->routes === null) {
            $this->routes = $this->getRoutingRules();
        }
        return $this->routes;
    }

    protected function getRoutingRules($route = array())
    {
        $routes = array();
        if ($this->getEnvironment() === 'backend') {
            $path = $this->getRoutingPath('backend');
            if (file_exists($path)) {
                $routes = array_merge($routes, include($path));
            }
        }

        $path = $this->getRoutingPath('frontend');
        if (file_exists($path)) {
            $routes = array_merge($routes, include($path));
        }
        return $routes;
    }

    protected function getRoutingPath($type)
    {
        if ($type === null) {
            $type = $this->getEnvironment();
        }
        $filename = ($type === 'backend') ? 'routing.backend.php' : 'routing.php';
        $path = $this->getConfigPath($filename, true, $this->application);
        if (!file_exists($path)) {
            $path = $this->getConfigPath($filename, false, $this->application);
        }
        return $path;
    }

    /**
     * TODO: The method that we will ever learn to use.
     */
    protected function getPluginRoutes($route)
    {
        /**
         * Extend routing via plugin routes
         * @event routing
         * @param array $routes
         * @return array $routes routes collected for every plugin
         */
        $result = wa()->event(array($this->application, 'routing'), $route);
        $all_plugins_routes = array();
        foreach ($result as $plugin_id => $routing_rules) {
            if (!$routing_rules) {
                continue;
            }
            $plugin = str_replace('-plugin', '', $plugin_id);
            foreach ($routing_rules as $url => & $route) {
                if (!is_array($route)) {
                    list($route_ar['module'], $route_ar['action']) = explode('/', $route) + array(1 => '');
                    $route = $route_ar;
                }
                if (!array_key_exists('plugin', $route)) {
                    $route['app'] = $this->application;
                    $route['plugin'] = $plugin;
                }
                $all_plugins_routes[$url] = $route;
            }
            unset($route);
        }
        return $all_plugins_routes;
    }

    public function getPrefix()
    {
        if (!$this->prefix) {
            $this->prefix = $this->getInfo('prefix');
            if (!$this->prefix) {
                $this->prefix = $this->getApplication();
            }
        }
        return $this->prefix;
    }

    /**
     * Returns app's name from its configuration file lib/config/app.php.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getInfo('name');
    }

    /**
     * Returns information from app's configuration file lib/config/app.php.
     *
     * @param string $name Name of parameter whose value must be returned. If not specified, method returns
     *     associative array of all parameters contained in configuration file.
     * @return string|array
     */
    public function getInfo($name = null)
    {
        if ($name === null) {
            return $this->info;
        } else {
            return isset($this->info[$name]) ? $this->info[$name] : null;
        }
    }

    /**
     * Returns path to the source files of an app's plugin.
     *
     * @param string $plugin_id Plugin id
     * @return string
     */
    public function getPluginPath($plugin_id)
    {
        return $this->getAppPath()."/plugins/".$plugin_id;
    }

    public function getWidgetPath($widget_id)
    {
        return $this->getAppPath()."/widgets/".$widget_id;
    }

    /**
     * Returns information about app's plugin.
     *
     * @param string $plugin_id Plugin id
     * @return array
     */
    public function getPluginInfo($plugin_id)
    {
        if ($this->plugins === null) {
            $this->getPlugins();
        }
        return isset($this->plugins[$plugin_id]) ? $this->plugins[$plugin_id] : array();
    }

    public function getWidgets()
    {
        if ($this->widgets === null) {
            $locale = wa()->getLocale();
            $file = waConfig::get('wa_path_cache')."/apps/".$this->application.'/config/widgets.'.$locale.'.php';
            if (!file_exists($file) || self::isDebug()) {
                $this->widgets = array();
                if ($this->application == 'webasyst') {
                    $path = $this->getRootPath().'/wa-widgets';
                } else {
                    $path = $this->getAppPath('widgets');
                }
                foreach (waFiles::listdir($path) as $widget_id) {
                    $widget_dir = $this->getWidgetPath($widget_id);
                    $widget_config = $widget_dir."/lib/config/widget.php";
                    if (!is_dir($widget_dir) || !file_exists($widget_config)) {
                        continue;
                    }
                    $widget_info = include($widget_config);
                    $widget_info['has_settings'] = file_exists($this->getWidgetPath($widget_id)."/lib/config/settings.php");
                    waSystem::pushActivePlugin($widget_id, $this->application == 'webasyst' ? 'widget' : $this->application.'_widget');
                    // Load widget locale if it exists
                    if ($this->application == 'webasyst') {
                        $locale_path = waConfig::get('wa_path_widgets').'/'.$widget_id.'/locale';
                    } else {
                        $locale_path = wa()->getAppPath('widgets/'.$widget_id.'/locale', $this->application);
                    }
                    if (is_dir($locale_path)) {
                        waLocale::load($locale, $locale_path, wa()->getActiveLocaleDomain(), false);
                    }
                    $widget_info['name'] = _wp($widget_info['name']);
                    if (isset($plugin_info['title'])) {
                        $widget_info['title'] = _wp($widget_info['title']);
                    }
                    if (isset($widget_info['description'])) {
                        $widget_info['description'] = _wp($widget_info['description']);
                    }
                    if (isset($widget_info['size'])) {
                        $sizes = array();
                        foreach ((array)$widget_info['size'] as $s) {
                            $sizes[] = explode('x', $s);
                        }
                        $widget_info['sizes'] = $sizes;
                    } else {
                        $widget_info['sizes'] = array(
                            array(1, 1)
                        );
                    }
                    waSystem::popActivePlugin();
                    $widget_info['widget'] = $widget_id;
                    $widget_info['app_id'] = $this->application;
                    if (isset($widget_info['img'])) {
                        if ($this->application == 'webasyst') {
                            $widget_info['img'] = 'wa-widgets/' . $widget_id . '/' . $widget_info['img'];
                        } else {
                            $widget_info['img'] = 'wa-apps/' . $this->application . '/widgets/' . $widget_id . '/' . $widget_info['img'];
                        }
                    }
                    $this->widgets[$widget_id] = $widget_info;
                }
                if (!self::isDebug()) {
                    waUtils::varExportToFile($this->widgets, $file);
                } else {
                    waFiles::delete($file);
                }
            } else {
                $this->widgets = include($file);
            }
        }
        return $this->widgets;
    }

    /**
     * Returns information about all app's installed plugins as an associative array.
     *
     * @return array
     */
    public function getPlugins()
    {
        if ($this->plugins === null) {
            $locale = wa()->getLocale();
            $file = waConfig::get('wa_path_cache')."/apps/".$this->application.'/config/plugins.'.$locale.'.php';
            if (!file_exists($file) || self::isDebug()) {
                waFiles::create(waConfig::get('wa_path_cache')."/apps/".$this->application.'/config');
                // read plugins from file wa-config/[APP_ID]/plugins.php
                $path = $this->getConfigPath('plugins.php', true);
                if (!file_exists($path)) {
                    $this->plugins = array();
                    return $this->plugins;
                }

                $all_plugins = include($path);
                $all_plugins = is_array($all_plugins) ? $all_plugins : [];

                $this->plugins = array();
                foreach ($all_plugins as $plugin_id => $enabled) {
                    if ($enabled) {
                        $plugin_config = $this->getPluginPath($plugin_id)."/lib/config/plugin.php";
                        if (!file_exists($plugin_config)) {
                            continue;
                        }
                        $plugin_info = include($plugin_config);
                        waSystem::pushActivePlugin($plugin_id, $this->application);
                        // Load plugin locale if it exists
                        $locale_path = wa()->getAppPath('plugins/'.$plugin_id.'/locale', $this->application);
                        if (is_dir($locale_path)) {
                            waLocale::load($locale, $locale_path, wa()->getActiveLocaleDomain(), false);
                        }
                        $plugin_info['name'] = _wp($plugin_info['name']);
                        if (isset($plugin_info['title'])) {
                            $plugin_info['title'] = _wp($plugin_info['title']);
                        }
                        if (isset($plugin_info['description'])) {
                            $plugin_info['description'] = _wp($plugin_info['description']);
                        }
                        waSystem::popActivePlugin();
                        $plugin_info['id'] = $plugin_id;
                        $plugin_info['app_id'] = $this->application;
                        if (isset($plugin_info['img'])) {
                            $plugin_info['img'] = 'wa-apps/'.$this->application.'/plugins/'.$plugin_id.'/'.$plugin_info['img'];
                        }
                        if (isset($plugin_info['rights']) && $plugin_info['rights']) {
                            if (!isset($plugin_info['handlers']['rights.config'])) {
                                $plugin_info['handlers']['rights.config'] = 'rightsConfig';
                            }
                        }
                        if (isset($plugin_info['frontend']) && $plugin_info['frontend']) {
                            if (!isset($plugin_info['handlers']['routing'])) {
                                $plugin_info['handlers']['routing'] = 'routing';
                            }
                        }
                        if (!empty($plugin_info[$this->application.'_settings'])) {
                            $plugin_info['custom_settings'] = $plugin_info[$this->application.'_settings'];
                        }
                        $this->plugins[$plugin_id] = $plugin_info;
                    }
                }
                if (!self::isDebug()) {
                    waUtils::varExportToFile($this->plugins, $file);
                } else {
                    waFiles::delete($file);
                }

            } else {
                $this->plugins = include($file);
            }
        }
        return $this->plugins;
    }

    /**
     *
     * Update general plugin sort
     * @param string $plugin plugin id
     * @param int $sort 0 is first
     * @throws waException
     */
    public function setPluginSort($plugin, $sort)
    {
        $path = $this->getConfigPath('plugins.php', true);
        if (file_exists($path) && ($plugins = include($path)) && !empty($plugins[$plugin])) {
            $sort = max(0, min(intval($sort), count($plugins) - 1));
            $order = array_flip(array_keys($plugins));
            if ($order[$plugin] != $sort) {
                $b = array($plugin => $plugins[$plugin]);
                unset($plugins[$plugin]);
                $a = array_slice($plugins, 0, $sort, true);
                $c = array_slice($plugins, $sort, null, true);
                $plugins = array_merge($a, $b, $c);
                if (waUtils::varExportToFile($plugins, $path)) {
                    waFiles::delete(waConfig::get('wa_path_cache')."/apps/".$this->application.'/config', true);
                } else {
                    throw new waException("Fail while update plugins sort order");
                }
            }
        }
    }

    public function checkRights($module, $action)
    {
        return true;
    }

    /**
     * The method returns a counter to show in backend header near applications' icons.
     * Three types of response are allowed.
     * @return string|int - A prime number in the form of a int or string
     * @return array - Array with keys 'count' - the value of the counter and 'url' - icon url
     * @return array - An associative array in which the key is the object key from app.php, from the header_items.
     *                 The value must be identical to the value described in one of the previous types of response.
     */
    public function onCount()
    {
        return null;
    }

    /**
     * Sets or clears the value of app's indicator displayed next to its icon in main backend menu.
     *
     * @param mixed $n Indicator value. If empty value is specified, indicator value is cleared.
     */
    public function setCount($n = null)
    {
        $count = wa()->getStorage()->get('apps-count');
        if (!$count) {
            $count = array();
        }
        if ($n) {
            $count[$this->application] = $n;
            wa()->getStorage()->set('apps-count', $count);
        } elseif ($count && isset($count[$this->application])) {
            unset($count[$this->application]);
            wa()->getStorage()->set('apps-count', $count);
        }
    }

    public function dispatchAppToken($data)
    {
        $m = new waAppTokensModel();
        $m->deleteById($data['token']);
    }

    public function throwFrontControllerDispatchException()
    {
        // Called when route is not found in backend routing, see waFrontController.
        // Overriden in webasystConfig because of backend dashboard logic.
        throw new waException('Page not found', 404);
    }
}
