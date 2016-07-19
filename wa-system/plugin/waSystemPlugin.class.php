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
 * to save and load key-value pairs. Plugin copies are distuingished by a unique $key
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

    /**
     * List of available plugins of given type.
     *
     * @param array $options    reserved for future use
     * @param string $type      'shipping' or 'payment'
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
     * @return array plugin settings as key => value
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
     * @param string $id            plugin class id
     * @param int $key              application-defined unique identifier to distuinguish between plugin entities
     * @param string $type          plugin type (i.e. shipping or payment)
     * @return waSystemPlugin
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
     * Used by application to draw plugin settongs page.
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
     * @param array $settings
     * @return array
     */
    public function saveSettings($settings = array())
    {
        // Validate and prepare defualt values for checkboxes etc.
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


    /**
     * Helper to retrieve app adapter.
     * See waShipping and waPayment for actual implementation.
     *
     * @throws waException
     * @return waAppPayment
     */
    abstract protected function getAdapter();
}
