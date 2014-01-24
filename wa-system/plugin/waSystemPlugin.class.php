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
     * Enter description here ...
     * @param waiPluginSettings $model
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
     * @param $options array
     * @return array
     */
    public static function enumerate($options = array(), $type = null)
    {
        $plugins = array();
        foreach (waFiles::listdir(self::getPath($type)) as $id) {
            if ($info = self::info($id, $options = array(), $type)) {
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
        if ($config_path && file_exists($config_path) && ($config = include($config_path))) {
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
        return $plugin;
    }

    protected function initControls()
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

    public function saveSettings($settings = array())
    {
        $settings_config = $this->config();
        foreach ($settings_config as $name => $row) {
            if (!isset($settings[$name])) {
                switch (ifset($row['control_type'])) {
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

        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            $this->getAdapter()->setSettings($this->id, $this->key, $name, $value);
        }
        return $settings;
    }
}
