<?php

/**
 * Common interface for system plugins: shipping and payment.
 *
 * System plugins are quite different from application plugins. System plugins
 * implement common functionality for all apps to use if apps are designed to do so.
 *
 * Unlike application plugins, for each system plugin there might be several copies
 * of a plugin with separate sets of settings.
 *
 * System plugins do not store their own settings. Instead, they use app adapter
 * to save and load key-value pairs. Plugin copies are distinguished by a unique $key
 * passed to a plugin constructor.
 *
 * System plugins and applications communicate via an adapter class that belongs to an app.
 * See: waAppShipping and waAppPayment (both inherit from waiPluginApp);
 * waShipping->getAdapter() and waPayment->getAdapter().
 */
abstract class waSystemPlugin
{
    /**
     * key=>value settings from adapter storage.
     * See $this->getSettings().
     * @var array
     */
    private $settings = null;
    /**
     * Cache for settings.php
     * @var array
     */
    private $config;
    /**
     * Path to plugin directory (no trailing slash).
     * @var string
     */
    protected $path;
    /**
     * Plugin class id
     * @var string
     */
    protected $id;
    /**
     * Identifier to pass to app adapter to save and load data
     * @var int
     */
    protected $key;
    /**
     * Plugin type (shipping, payment etc.)
     * @var string
     */
    protected $type;

    /**
     * Should not be called directly. Use `waShipping::factory()` and `waPayment::factory()` instead.
     *
     * @param string $key
     * @throws waException
     */
    final protected function __construct($key = null)
    {
        $this->key = $key;
    }

    private function __clone()
    {
    }

    protected function init()
    {
        waAutoload::getInstance()->add(self::getPluginClasses($this->type, $this->id));
        $this->checkUpdates();
    }

    protected static function getPluginClasses($type, $id)
    {
        $app_id = sprintf('webasyst/%s/%s', $type, $id);
        $cache_file = waConfig::get('wa_path_cache').'/apps/'.$app_id.'/config/autoload.php';

        $result = null;

        if (!waSystemConfig::isDebug() && file_exists($cache_file)) {
            $result = @include($cache_file);
            if (!is_array($result)) {
                $result = null;
            }
        }

        if ($result === null) {

            $lib_path = self::getPath($type, $id, 'lib');


            $autoload = waAutoload::getInstance();

            $base_path = waConfig::get('wa_path_root');

            $result = array();
            $length = strlen($base_path) + 1;

            $paths = waFiles::listdir($lib_path);
            foreach ($paths as $path) {
                if (!in_array($path, array('vendor', 'vendors', 'updates', 'config'))) {
                    $path = $lib_path.'/'.$path;
                    if (@is_dir($path) && ($files = self::getPHPFiles($path.'/'))) {
                        foreach ($files as $file) {
                            $class = $autoload->getClassByFilename(basename($file), '');
                            if ($class) {
                                $result[$class] = substr($file, $length);
                            }
                        }
                    }
                }
            }

            if (!waSystemConfig::isDebug()) {
                waFiles::create($cache_file);
                waUtils::varExportToFile($result, $cache_file);
            } else {
                waFiles::delete($cache_file);
            }
        }

        return $result;
    }

    protected static function getPHPFiles($path)
    {
        if (!($dh = opendir($path))) {
            throw new waException('Filed to open dir: '.$path);
        }
        $result = array();
        while (($f = readdir($dh)) !== false) {
            if (self::isIgnoreFile($f)) {
                continue;
            } elseif (is_dir($path.$f)) {
                $result = array_merge($result, self::getPHPFiles($path.$f.'/'));
            } elseif (substr($f, -4) == '.php') {
                $result[] = $path.$f;
            }
        }
        closedir($dh);
        return $result;
    }

    protected static function isIgnoreFile($f)
    {
        return $f === '.' || $f === '..' || $f === '.svn' || $f === '.git';
    }

    /**
     * List of available plugins of given type.
     *
     * @param array $options reserved for future use
     * @param string $type 'shipping' or 'payment'
     * @return array            plugin class id => plugin info: see waSystemPlugin::info()
     */
    public static function enumerate($options = array(), $type = null)
    {
        $plugins = array();
        foreach (waFiles::listdir(self::getPath($type)) as $id) {
            $info = self::info($id, $options, $type);
            if ($info) {
                $plugins[$id] = $info;
            }
        }
        return $plugins;
    }

    protected static function getPath($type, $id = null, $path_tail = null)
    {
        if (!$type) {
            throw new waException('Invalid method usage');
        }
        $path = waSystem::getInstance()->getConfig()->getPath('plugins');
        $path .= DIRECTORY_SEPARATOR.$type;
        if ($id) {
            if (!preg_match('@^[a-z][a-z0-9_]*$@', $id)) {
                return null;
            }
            $path .= DIRECTORY_SEPARATOR.$id;

        }
        if ($path_tail) {
            $path .= DIRECTORY_SEPARATOR.$path_tail;
        }
        return $path;
    }

    /**
     *
     * Get plugin description
     * @param string $id
     * @param array $options
     *
     * @return array[string]string
     * @return array['name']string
     * @return array['description']string
     * @return array['version']string
     * @return array['build']string
     * @return array['logo']string
     * @return array['icon'][int]string
     * @return array['img']string
     */
    public static function info($id, $options = array(), $type = null)
    {
        $base_path = self::getPath($type, $id);
        $config_path = $base_path.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'plugin.php';

        $plugin = null;
        if ($base_path && file_exists($base_path)
            && $config_path && file_exists($config_path)
            && ($config = include($config_path))
        ) {
            $default = array(
                'name'        => preg_replace('@[A-Z]@', ' $1', $id),
                'description' => '',
            );
            if (!is_array($config)) {
                $config = array();
            }
            $build_file = $base_path.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'build.php';
            if (file_exists($build_file)) {
                $config['build'] = include($build_file);
            } else {
                if (SystemConfig::isDebug()) {
                    $config['build'] = time();
                } else {
                    $config['build'] = 0;
                }
            }
            if (!empty($config['version'])) {
                $config['version'] .= '.'.$config['build'];
            }
            if (!empty($config['icon'])) {
                if (is_array($config['icon'])) {
                    foreach ($config['icon'] as $size => $url) {
                        $config['icon'][$size] = wa()->getRootUrl().'wa-plugins/'.$type.'/'.$id.'/'.$url;
                    }
                } else {
                    $config['icon'] = array(
                        48 => wa()->getRootUrl().'wa-plugins/'.$type.'/'.$id.'/'.$config['icon'],
                    );
                }
            } else {
                $config['icon'] = array();
            }
            if (!empty($config['img'])) {
                $config['img'] = wa()->getRootUrl().'wa-plugins/'.$type.'/'.$id.'/'.$config['img'];
            } else {
                $config['img'] = isset($config['icon'][48]) ? $config['icon'][48] : false;
            }
            if (!isset($config['icon'][48])) {
                $config['icon'][48] = $config['img'];
            }
            if (!isset($config['icon'][24])) {
                $config['icon'][24] = $config['icon'][48];
            }
            if (!isset($config['icon'][16])) {
                $config['icon'][16] = $config['icon'][24];
            }
            if (!isset($config['logo'])) {
                $config['logo'] = $config['icon'][48];
            } elseif (!empty($config['logo'])) {
                $config['logo'] = wa()->getRootUrl().'wa-plugins/'.$type.'/'.$id.'/'.$config['logo'];
            }
            $plugin = array_merge($default, $config);

            foreach (array('name', 'description') as $field) {
                if (!empty($plugin[$field])) {
                    $plugin[$field] = self::__w($plugin[$field], $type, $id, $base_path);
                }
            }
        }
        return $plugin;
    }

    /**
     * @param string $name
     * @return array|string plugin settings as key => value or value only if name specified
     */
    public function getSettings($name = null)
    {
        if ($this->settings === null) {
            $this->setSettings();
        }
        if ($name === null) {
            return $this->settings;
        } else {
            return isset($this->settings[$name]) ? $this->settings[$name] : null;
        }
    }

    /**
     * Populate $this->settings by copying $settings there, then load all missing values
     * with defaults from settings.php config file.
     * @param array $settings
     */
    protected function setSettings($settings = array())
    {
        $this->settings = array();
        if ($config = $this->config()) {
            $this->settings = $settings;
            foreach ($config as $key => $default) {
                if (!isset($this->settings[$key])) {
                    $value = null;
                    if (isset($default['value'])) {
                        $value = $default['value'];
                        if (!empty($default['control_type']) && ($default['control_type'] == waHtmlControl::INPUT)) {
                            $value = $this->_w($value);
                        }
                    }
                    $this->settings[$key] = $value;
                }
            }
        }
    }

    /**
     * @return string plugin class id
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * Return plugin property, described in plugin.php config
     *
     * @param string $property
     * @return mixed
     */
    final public function getProperties($property = null)
    {
        if (!isset($this->properties)) {
            $this->properties = $this->info($this->id);
        }
        return isset($property) ? (isset($this->properties[$property]) ? $this->properties[$property] : false) : $this->properties;
    }

    /** @return string */
    final public function getName()
    {
        return $this->getProperties('name');
    }

    /** @return string */
    final public function getDescription()
    {
        return $this->getProperties('description');
    }

    final public function getType()
    {
        return $this->getProperties('type');
    }

    public function getPluginKey()
    {
        return $this->key;
    }

    public function __get($name)
    {
        return $this->getSettings($name);
    }

    public function __isset($name)
    {
        return $this->getSettings($name) !== null;
    }

    /**
     * Localization for system plugins.
     * Just like the _w(), translates a single string
     * or selects translation option based on number.
     *
     * @param string $msgid1
     * @param string $msgid2
     * @param int $n
     * @param bool $sprintf
     * @return string
     */
    public function _w($string)
    {
        $args = func_get_args();
        return self::__w($args, $this->type, $this->id, $this->path);
    }

    private static function __w($string, $type, $id, $path)
    {
        static $domains = array();
        $domain = sprintf('%s_%s', $type, $id);
        if (!isset($domains[$domain])) {
            $locale_path = $path.'/locale';
            if ($domains[$domain] = file_exists($locale_path)) {
                waLocale::load(waLocale::getLocale(), $locale_path, $domain, false);
            }
        }

        $args = (array)$string;
        if ($domains[$domain]) {
            array_unshift($args, $domain);
            $string = call_user_func_array('_wd', $args);
        } else {
            $string = reset($args);
        }
        return $string;
    }

    /**
     * Helper to properly instantiate a system plugin. Should never be used directly,
     * see `waShipping::factory()` and `waPayment::factory()` instead.
     *
     * @param string $id plugin class id
     * @param int $key application-defined unique identifier to distuinguish between plugin entities
     * @param string $type plugin type (i.e. shipping or payment)
     * @return waSystemPlugin
     * @throws waException
     */
    public static function factory($id, $key = null, $type = null)
    {
        $id = strtolower($id);
        $base_path = self::getPath($type, $id);
        if (!$base_path) {
            throw new waException(sprintf('Invalid module ID %s', $id));
        }
        $path = $base_path.sprintf('%2$slib%2$s%1$s%3$s.class.php', $id, DIRECTORY_SEPARATOR, ucfirst($type));
        if (file_exists($path)) {
            require_once($path);
        }
        $class = $id.ucfirst($type);

        if (class_exists($class)) {
            $plugin = new $class($key);
            if (!($plugin instanceof self)) {
                throw new waException('Invalid parent class');
            }
            $plugin->path = $base_path;
            $plugin->id = $id;
            $plugin->type = $type;
        } else {
            $exception_messages = array(
                'shipping' => _ws('Shipping plugin class “%s” not found.'),
                'payment' => _ws('Payment plugin class “%s” not found.'),
                'sms' => _ws('SMS plugin class “%s” not found.'),
            );

            throw new waException(sprintf(ifset($exception_messages, $type, ucfirst($type) . ' plugin class “%s” not found.'), $class));
        }
        return $plugin;
    }

    /**
     * Override this to set up custom controls used in settings.php config.
     * See $this->registerControl().
     */
    protected function initControls()
    {
    }

    /**
     * Register user input control. See waHtmlControl::registerControl().
     *
     * @param string $type
     * @param callback $callback
     * @return self Current object
     * @throws Exception
     */
    protected function registerControl($type, $callback = null)
    {
        if (is_null($callback)) {
            $callback = array(get_class($this), "setting{$type}");
        }
        waHtmlControl::registerControl($type, $callback);
        return $this;
    }

    protected function getSettingsDefaultParams($params = array())
    {
        $default = array(
            'instance'            => &$this,
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'translate'           => array(&$this, '_w'),
            'control_wrapper'     => '
<div class="field">
    <div class="name">%s</div>
    <div class="value">%s%s</div>
</div>
',
            'control_separator'   => '</div><div class="value">',
        );

        return array_merge($default, $params);
    }

    /**
     * Used by application to draw plugin settings page.
     *
     * @param array [string]mixed $params
     * @param array [string]string $params['namespace']
     * @param array [string]string $params['value']'
     * @return string
     */
    public function getSettingsHTML($params = array())
    {
        $this->initControls();
        $controls = array();

        $params = $this->getSettingsDefaultParams($params);
        $options = ifempty($params['options'], array());
        unset($params['options']);

        foreach ($this->config() as $name => $row) {
            $row = array_merge($row, $params);

            if (!empty($row['control_type'])) {
                if (isset($options[$name])) {
                    $row['options'] = $options[$name];
                }
                if (isset($params['value']) && isset($params['value'][$name])) {
                    $row['value'] = $params['value'][$name];
                } elseif ($row['control_type'] === waHtmlControl::DATETIME) {
                    $row['value'] = array(
                        'date_str' => $this->getSettings($name.'.date_str'),
                        'date'     => $this->getSettings($name.'.date'),
                    );
                } else {
                    $row['value'] = $this->getSettings($name);
                }
                try {
                    $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
                } catch (Exception $ex) {
                    $controls[$name] = $ex->getMessage();
                }
            }
        }
        return implode("\n", $controls);
    }

    private function config()
    {
        if ($this->config === null) {
            if ($this->path) {
                $path = $this->path.'/lib/config/settings.php';
                if (file_exists($path)) {
                    $this->config = include($path);

                    if (is_array($this->config)) {
                        foreach ($this->config as & $config) {
                            if (isset($config['title'])) {
                                $config['title'] = $this->_w($config['title']);
                            }
                            if (isset($config['description'])) {
                                $config['description'] = $this->_w($config['description']);
                            }
                        }
                        unset($config);
                    } else {
                        $this->config = array();
                    }
                }
            }
            if (!is_array($this->config)) {
                $this->config = array();
            }
        }
        return $this->config;
    }

    /**
     * @param array $settings
     * @return array
     * @throws waException
     */
    public function saveSettings($settings = array())
    {
        // Validate and prepare default values for checkboxes etc.
        $settings_config = $this->config();
        foreach ($settings_config as $name => $row) {
            if (!isset($settings[$name])) {
                switch (preg_replace('@\s.*$@', '', ifset($row['control_type']))) {
                    case waHtmlControl::CHECKBOX:
                        $settings[$name] = false;
                        break;
                    case waHtmlControl::GROUPBOX:
                        $settings[$name] = array();
                        break;
                    default:
                        $settings[$name] = isset($row['value']) ? $row['value'] : null;
                        break;
                }
            } else {
                switch (ifset($row['control_type'])) {
                    // Check if file is uploaded properly
                    case waHtmlControl::FILE:
                        $file = waRequest::file($name);
                        if (!empty($file)) {
                            $settings[$name] = $file;
                        }
                        break;
                }
            }
        }

        // Save settings via app adapter
        $adapter = $this->getAdapter();
        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            $adapter->setSettings($this->id, $this->key, $name, $value);
        }
        return $settings;
    }

    private function getGeneralSettingsKey()
    {
        return sprintf('webasyst.%s.%s', $this->type, $this->id);
    }

    /**
     * @param string $name
     * @param string $default
     * @return string[]|string
     * @throws waDbException
     * @since 1.13 framework version
     */
    public function getGeneralSettings($name = null, $default = '')
    {
        $app_settings_model = new waAppSettingsModel();
        return $app_settings_model->get($this->getGeneralSettingsKey(), $name, $default);
    }

    /**
     * @param string $name
     * @param string $value
     * @throws waDbException
     * @since 1.13 framework version
     */
    public function setGeneralSettings($name, $value)
    {
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set($this->getGeneralSettingsKey(), $name, $value);
    }

    /**
     * @param string $table
     * @return waModel
     * @throws waException
     * @since 1.13 framework version
     */
    public function getModel($table = null)
    {
        if (waConfig::get('is_template')) {
            throw new waException('access from template is not allowed');
        }

        /** @var string $class mypluginPaymentMytableModel or mypluginShippingMytableModel */
        $class = $this->id.ucfirst($this->type).($table ? ucfirst($table) : '').'Model';

        if (!class_exists($class)) {
            $class = 'waSystemPluginModel';
        }

        $model = new $class();

        if ($model instanceof waSystemPluginModel) {
            $model->setPlugin($this, $table);
            $model->getMetadata();
        }

        return $model;
    }

    private function getControllerInstance($class_name)
    {
        $controller = null;
        if (class_exists($class_name)) {
            $controller = new $class_name();
            if ($controller instanceof waSystemPluginActions) {
                $controller->setPlugin($this);
            } elseif ($controller instanceof waSystemPluginAction) {
                $controller->setPlugin($this);
            }
        }

        return $controller;
    }

    /**
     * @param string $module
     * @param string $action
     * @return waSystemPluginActions|waSystemPluginAction|waController|waJsonActions|waJsonController
     * @throws waException
     * @since 1.13 framework version
     */
    public function getController($module = 'backend', $action = 'Default')
    {
        if (waConfig::get('is_template')) {
            throw new waException('access from template is not allowed');
        }

        $params = null;
        if (empty($controller)) {
            // Single Controller (recommended)
            /** @var string $class_name e.g. mypluginPaymentBackendDefaultController or  mypluginShippingBackendDefaultController */
            $class_name = sprintf('%s%s%s%sController', $this->id, ucfirst($this->type), ucfirst($module), ucfirst($action));
            $class_names[] = $class_name;

            $controller = $this->getControllerInstance($class_name);
        }

        if (empty($controller)) {
            // Single Action
            /** @var string $class_name e.g. mypluginPaymentBackendDefaultAction or  mypluginShippingBackendDefaultAction */
            $class_name = sprintf('%s%s%s%sAction', $this->id, ucfirst($this->type), ucfirst($module), ucfirst($action));
            $class_names[] = $class_name;

            if ($instance = $this->getControllerInstance($class_name)) {
                /** @var $controller waDefaultViewController */
                $controller = wa()->getDefaultController();
                $controller->setAction($instance);
            }
        }

        if (empty($controller)) {
            // Controller Multi Actions, Zend/Symfony style
            /** @var string $class_name e.g. mypluginPaymentBackendActions or  mypluginShippingBackendActions */
            $class_name = sprintf('%s%s%sActions', $this->id, ucfirst($this->type), ucfirst($module));
            $class_names[] = $class_name;

            if ($controller = $this->getControllerInstance($class_name)) {
                $params = $action;
            }
        }

        if (empty($controller)) {
            $message = sprintf(
                'Empty module and/or action after parsing the URL "%s" (%s/%s).<br />Not found classes: %s',
                wa()->getConfig()->getCurrentUrl(),
                $module,
                $action,
                implode(', ', $class_names)
            );
            throw new waException($message);
        }

        waRequest::setParam('plugin_params', $params);

        return $controller;
    }

    /**
     * @param string|null $path   Optional path to a subdirectory in main directory with user data files.
     * @param bool        $public Flag requiring to return path to the subdirectory used for storing files publicly
     *                            accessible without authorization, by direct link. If 'false' (default value), then method returns path to
     *                            subdirectory used for storing files accessible only upon authorization in the backend.
     * @param bool        $create Flag requiring to create a new directory directory at the specified path if it is
     *                            missing. New directories are created by default if 'false' is not specified.
     * @return string
     * @since 1.13 framework version
     */
    public function getDataPath($path = null, $public = false, $create = true)
    {
        $app_id = sprintf('webasyst/%s/%s', $this->type, $this->id);
        return wa()->getDataPath($path, $public, $app_id, $create);
    }

    /**
     * @param string|null $path     Optional path to a subdirectory in main directory with user data files.
     * @param bool        $absolute Return absolute URL instead of the relative one (default value).
     * @return string
     * @since 1.13 framework version
     */
    public function getDataUrl($path = null, $absolute = false)
    {
        $app_id = sprintf('webasyst/%s/%s', $this->type, $this->id);
        return wa()->getDataUrl($path, true, $app_id, $absolute);
    }

    private function install()
    {
        // Create database scheme
        $file_db = self::getPath($this->type, $this->id, 'lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            $model->createSchema($schema);
        }

        // Mark localization files as recently changed.
        // This forces use of PHP localization adapter that does not get stuck in apache cache.
        $locale_path = self::getPath($this->type, $this->id, 'locale');
        if (file_exists($locale_path)) {
            $all_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($locale_path));
            $po_files = new RegexIterator($all_files, '~(\.po)$~i');
            foreach ($po_files as $f) {
                @touch($f->getPathname());
            }
        }

        // Installation script of the app
        $file = self::getPath($this->type, $this->id, 'lib/config/install.php');
        if (file_exists($file)) {
            $id = $this->id;
            /** @var string $id */
            include($file);
        }
    }


    protected function checkUpdates()
    {
        $time = $this->getGeneralSettings('update_time', null);

        // Install the plugin and remember to skip all updates
        // if this is the first launch.
        $is_first_launch = false;
        $is_from_template = waConfig::get('is_template');
        if (empty($time)) {
            $time = null;
            $is_first_launch = true;

            try {
                waConfig::set('is_template', null);
                $this->install();
                waConfig::set('is_template', $is_from_template);
            } catch (waException $e) {
                waLog::log("Error installing {$this->type} plugin {$this->id} at first run:\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                waConfig::set('is_template', $is_from_template);
                throw $e;
            }
        }

        // Use cache to skip slow filesystem-based scanning for updates
        if (!SystemConfig::isDebug()) {
            $cache = new waVarExportCache('updates', -1, $this->getGeneralSettingsKey());
            if ($time && $cache->isCached() && $cache->get() <= $time) {
                return;
            }
        }

        // Scan for app updates
        $files = $this->getUpdateFiles(self::getPath($this->type, $this->id, 'lib/updates'), $time);
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

        if ($is_first_launch) {
            // Updates are all skipped on app's first launch with install.php
            $this->setGeneralSettings('update_time', $last_update_ts);

        } elseif ($files) {
            waConfig::set('is_template', null);
            $cache_database_dir = wa()->getConfig()->getPath('cache').'/db';
            foreach ($files as $t => $file) {
                try {

                    if (SystemConfig::isDebug()) {
                        waLog::dump(sprintf('Try include file %s by %s plugin %s', $file, $this->type, $this->id), 'meta_update.log');
                    }

                    $this->includeCode($file);
                    waFiles::delete($cache_database_dir, true);
                    $this->setGeneralSettings('update_time', $t);
                } catch (Exception $e) {
                    if (SystemConfig::isDebug()) {
                        echo $e;
                    }
                    // log errors
                    waLog::log("Error running update of {$this->type} plugin {$this->id}: {$file}\n".$e->getMessage()." (".$e->getCode().")\n".$e->getTraceAsString());
                    break;
                }
            }
            waConfig::set('is_template', $is_from_template);
        }
    }

    /**
     * @param bool $force
     * @throws waDbException
     * @throws waException
     * @todo  call it at installer app
     * @since 1.13 framework version
     */
    public function uninstall($force = false)
    {
        // uninstall script of the plugin
        $file = self::getPath($this->type, $this->id, 'lib/config/uninstall.php');
        if (file_exists($file)) {
            try {
                $this->includeCode($file);
            } catch (Exception $ex) {
                if ($force) {
                    waLog::log(sprintf("Error while uninstall %s plugin %s at %s: %s", $this->type, $this->id, $ex->getMessage(), 'installer.log'));
                } else {
                    throw $ex;
                }
            }
        }
        $file_db = self::getPath($this->type, $this->id, 'lib/config/db.php');
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            foreach ($schema as $table => $fields) {
                $sql = "DROP TABLE IF EXISTS ".$table;
                $model->exec($sql);
            }
        }

        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->del($this->getGeneralSettingsKey());

        waFiles::delete($this->getDataPath('', true, false), true);
        waFiles::delete($this->getDataPath('', false, false), true);
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

    private function includeCode($file)
    {
        return include($file);
    }

    /** @since 1.13 framework version */
    abstract public function getInteractionUrl($action = 'default', $module = 'backend');


    /**
     * Helper to retrieve app adapter.
     * See waShipping and waPayment for actual implementation.
     *
     * @return waiPluginApp
     * @throws waException
     */
    abstract protected function getAdapter();
}
