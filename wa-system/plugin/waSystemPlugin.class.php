<?php

abstract class waSystemPlugin
{

    private $settings = null;
    private $config;
    protected $path;

    /**
     *
     * Plugin class id
     * @var string
     */
    protected $id;
    protected $key;
    /**
     *
     * Plugin type (shipping, payment etc.)
     * @var string
     */
    protected $type;

    /**
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

    /**
     * Список доступных плагинов
     * @param array $options
     * @param string $type
     * @return array
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

    protected static function getPath($type, $id = null)
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
        return $path;
    }

    /**
     *
     * Get plugin description
     * @param string $id
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
     *
     * Получение списка настраиваемых значений модуля доставки
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
     *
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
     *
     * @return string
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     *
     * Return plugin property, described at plugin config
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

    /**
     * @return string
     */
    final public function getName()
    {
        return $this->getProperties('name');
    }

    final public function getDescription()
    {
        return $this->getProperties('description');
    }

    final public function getType()
    {
        return $this->getProperties('type');
    }

    public function __get($name)
    {
        return $this->getSettings($name);
    }

    public function __isset($name)
    {
        return $this->getSettings($name) !== null;
    }

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
     *
     * Get shipping plugin
     * @param string $id
     * @param waiPluginSettings $adapter
     * @return waShipping
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
            throw new waException(sprintf("%s plugin class %s not found ", $type, $class));
        }

        $plugin->checkUpdates();

        return $plugin;
    }

    /**
     * Checks for installation or update scripts and runs them
     *
     * @throws Exception
     */
    protected function checkUpdates()
    {
        $app_settings_model = new waAppSettingsModel();
        $full_id = "{$this->type}_{$this->id}";
        $time = $app_settings_model->get(array('webasyst', $full_id), 'update_time');
        if (!$time) {
            try {
                $this->install();
            } catch (Exception $e) {
                waLog::log($e->getMessage());
                throw $e;
            }
            $ignore_all = true;
        } else {
            $ignore_all = false;
        }

        $is_debug = waSystemConfig::isDebug();

        if (!$is_debug) {
            $cache = new waVarExportCache('updates', 0, "webasyst.".$full_id);
            if ($cache->isCached() && $cache->get() <= $time) {
                return;
            }
        }
        $path = $this->path.'/lib/updates';
        $cache_database_dir = wa()->getConfig()->getPath('cache').'/db';
        if (file_exists($path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
            $files = array();
            foreach ($iterator as $file) {
                /**
                 * @var SplFileInfo $file
                 */
                if ($file->isFile() && preg_match('/^[0-9]+\.php$/', $file->getFilename())) {
                    $t = substr($file->getFilename(), 0, -4);
                    if ($t > $time) {
                        $files[$t] = $file->getPathname();
                    }
                }
            }
            ksort($files);
            if (!$is_debug && !empty($cache)) {
                // get last time
                if ($files) {
                    $keys = array_keys($files);
                    $cache->set(end($keys));
                } else {
                    $cache->set($time ? $time : 1);
                }
            }
            foreach ($files as $t => $file) {
                try {
                    if (!$ignore_all) {
                        $this->includeUpdate($file);
                        waFiles::delete($cache_database_dir);
                        $app_settings_model->set(array('webasyst', $full_id), 'update_time', $t);
                    }
                } catch (Exception $e) {
                    if ($is_debug) {
                        echo $e;
                    }
                    // log errors
                    waLog::log($e->__toString());
                    break;
                }
            }
        } else {
            $t = 1;
        }

        if ($ignore_all) {
            if (!isset($t) || !$t) {
                $t = 1;
            }
            $app_settings_model->set(array('webasyst', $full_id), 'update_time', $t);
        }
    }

    protected function initControls()
    {
    }

    /**
     * @todo write smth
     */
    protected function install()
    {
    }

    /**
     * Register user input control
     * @param string $type
     * @param callback $callback
     * @return waShipping Current object
     */
    protected function registerControl($type, $callback = null)
    {
        if (is_null($callback)) {
            $callback = array(get_class($this), "setting{$type}");
        }
        waHtmlControl::registerControl($type, $callback);
        return $this;
    }

    /**
     *
     * Получение массива элементов настроек
     * @param array [string]mixed $params
     * @param array [string]string $params['namespace']
     * @param array [string]string $params['value']'
     * @return string
     */
    public function getSettingsHTML($params = array())
    {
        $this->initControls();
        $controls = array();
        $default = array(
            'instance'            => & $this,
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
        $options = ifempty($params['options'], array());
        unset($params['options']);
        $params = array_merge($default, $params);

        foreach ($this->config() as $name => $row) {
            $row = array_merge($row, $params);
            $row['value'] = $this->getSettings($name);
            if (isset($options[$name])) {
                $row['options'] = $options[$name];
            }
            if (isset($params['value']) && isset($params['value'][$name])) {
                $row['value'] = $params['value'][$name];
            }
            if (!empty($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
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

                    foreach ($this->config as & $config) {
                        if (isset($config['title'])) {
                            $config['title'] = $this->_w($config['title']);
                        }
                        if (isset($config['description'])) {
                            $config['description'] = $this->_w($config['description']);
                        }
                    }
                    unset($config);
                }
            }
            if (!is_array($this->config)) {
                $this->config = array();
            }
        }
        return $this->config;
    }

    /**
     *
     * Инициализация значений настроек модуля доставки
     */

    /**
     * @param array $settings
     * @return array
     */
    public function saveSettings($settings = array())
    {
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
                    case waHtmlControl::FILE:
                        $file = waRequest::file($name);
                        break;
                }
            }
        }

        $adapter = $this->getAdapter();

        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            $adapter->setSettings($this->id, $this->key, $name, $value);
        }
        return $settings;
    }

    /**
     * @param bool $force
     * @throws Exception
     * @throws waException
     */
    public function uninstall($force = false)
    {
        $full_id = "{$this->type}_{$this->id}";
        $info = array('type' => $this->type, 'id' => $this->id);

        // check uninstall.php
        $uninstall_script = $this->path.'/lib/config/uninstall.php';
        if (file_exists($uninstall_script) && ($force === true)) {
            try {
                include($uninstall_script);
            } catch (Exception $ex) {
                if ($force) {
                    waLog::log(sprintf("Error while uninstall %s plugin %s: %s", $this->type, $this->id, $ex->getMessage(), 'installer.log'));
                } else {
                    throw $ex;
                }
            }
        }

        $file_db = $this->path.'/lib/config/db.php';
        if (file_exists($file_db)) {
            $schema = include($file_db);
            $model = new waModel();
            foreach (array_keys($schema) as $table) {
                $sql = "DROP TABLE IF EXISTS ".$table;
                $model->exec($sql);
            }
        }
        // Remove plugin settings
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->del("webasyst.".$full_id);

        // Remove cache of the application
        waFiles::delete(wa()->getAppCachePath('', 'webasyst'));

        /**
         * @event uninstall_system_plugin
         */
        wa()->event(array('webasyst', 'uninstall_system_plugin'), $info);
    }

    /**
     * Includes file with update
     *
     * @param string $file
     */
    private function includeUpdate($file)
    {
        include($file);
    }

    /**
     *
     * @throws waException
     * @return waAppPayment
     */
    abstract protected function getAdapter();
}
