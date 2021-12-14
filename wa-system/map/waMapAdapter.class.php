<?php

abstract class waMapAdapter
{
    protected $controls = array();
    protected $settings = null;
    protected $env = null;

    const FRONTEND_ENVIRONMENT = 'frontend';
    const BACKEND_ENVIRONMENT = 'backend';

    /**
     * @var waAppSettingsModel
     */
    protected static $app_settings_model;

    /**
     * @param string|array $address - string or array(LAT, LNG)
     * @param array $options - map options
     *    int|string $option['width'] [optional] - width of map html dom element, for example '50%',
     *    int|string $option['height'] [optional] - height of map html dom element, for example '200px',
     *    int $option['zoom'] [optional] - zoom of map, for example 12
     *
     *
     *   string  $options['on_error'] [optional] - What to do on error (for now supports only by yandex map adapter)
     *     - 'show' - show error as it right on map html block
     *     - 'function(e) { ... }' - anonymous js function
     *     - any other NOT EMPTY string that is javascript function name in global scope (for example, console.log)
     *     - <empty> - not handle map error
     *
     * @return string
     */
    public function getHTML($address, $options = array())
    {
        if ($address) {
            if (is_string($address)) {
                return $this->getByAddress($address, $options);
            } elseif (is_array($address) && isset($address[0]) && isset($address[1])) {
                return $this->getByLatLng($address[0], $address[1], $options);
            }
        }
        return '';
    }

    /**
     * @param $address
     * @return array <pre>
     * array(
     *  'lat'=>float,
     *  'lng'=>float,
     * )
     * </pre>
     */
    public function geocode($address)
    {
        return array();
    }

    abstract public function getJs($html = true);

    /**
     * @return string
     */
    public function getId()
    {
        $class = get_class($this);
        return substr($class, 0, -3);
    }

    /**
     * @return string
     */
    public function getName()
    {
        $class = get_class($this);
        return ucfirst(substr($class, 0, -3));
    }

    protected function initControls()
    {
        $this->controls = array();
    }

    /**
     * @param array $params
     * @return string
     */
    public function getSettingsHtml($params = array())
    {
        waHtmlControl::addNamespace($params, $this->getId());
        $this->initControls();
        $controls = array();
        $default = array(
            'instance'            => & $this,
            'title_wrapper'       => '%s',
            'description_wrapper' => wa()->whichUI() == '2.0' ? '<p class="hint">%s</p>' : '<br><span class="hint">%s</span>',
            'control_wrapper'     => '
<div class="field">
    <div class="name' . ( wa()->whichUI() == '2.0' ? ' for-input' : '') .'">%s</div>
    <div class="value">%s%s</div>
</div>
',
            'control_separator'   => '</div><div class="value">',
        );
        $options = ifempty($params['options'], array());
        unset($params['options']);

        $params = array_merge($default, $params);
        foreach ($this->controls as $name => $row) {
            $row = array_merge($row, $params);
            if (isset($options[$name])) {
                $row['options'] = $options[$name];
            }

            $row['value'] = $this->getSettings($name);

            if (!empty($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
            }
        }
        return implode("\n", $controls);
    }

    protected function getSettingsKey()
    {
        $map_adapter_environment = 'map_adapter_%s';
        if (wa()->getEnv() == 'backend' && (is_null($this->env) || $this->env == self::BACKEND_ENVIRONMENT)) {
            $map_adapter_environment = 'backend_' . $map_adapter_environment;
        }

        return sprintf($map_adapter_environment, $this->getId());
    }

    protected function geocodingAllowed($allowed = null)
    {
        $sm = self::getSettingsModel();
        $app_id = 'webasyst';
        $env = '';
        if (wa()->getEnv() == 'backend') {
            $env = 'backend.';
        }
        $name = $env . 'geocoding.' . $this->getId();
        if ($allowed === null) {
            $last_geocoding = $sm->get($app_id, $name, 0);
            return ((time() - $last_geocoding) >= 3600);
        } elseif ($allowed) {
            $sm->del($app_id, $name);
            return true;
        } else {
            $sm->set($app_id, $name, time());
            return false;
        }
    }

    /**
     * @return waAppSettingsModel
     */
    protected static function getSettingsModel()
    {
        if (!self::$app_settings_model) {
            self::$app_settings_model = new waAppSettingsModel();
        }
        return self::$app_settings_model;
    }

    public function getSettings($name = null)
    {
        if (!isset($this->settings[$this->env])) {
            $settings = self::getSettingsModel()->get('webasyst', $this->getSettingsKey(), '{}');
            $this->settings[$this->env] = @json_decode($settings, true);
            foreach ($this->settings[$this->env] as $key => $value) {
                #decode non string values
                if (!is_numeric($value)) {
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$this->env][$key] = $json;
                    }
                }
            }

            $this->initControls();
            foreach ($this->controls as $key => $row) {
                if (!isset($this->settings[$this->env][$key])) {
                    $this->settings[$this->env][$key] = is_array($row) ? (isset($row['value']) ? $row['value'] : null) : $row;
                }
            }
        }
        if ($name === null) {
            return $this->settings[$this->env];
        } else {
            return isset($this->settings[$this->env][$name]) ? $this->settings[$this->env][$name] : null;
        }
    }

    /**
     * @param mixed [string] $settings Array of settings key=>value
     * @return void|array
     */
    public function saveSettings($settings = array())
    {
        $data = $this->getSettings();
        foreach ($this->controls as $name => $row) {
            if (!isset($settings[$name])) {
                if ((ifset($row['control_type']) == waHtmlControl::CHECKBOX) && !empty($row['value'])) {
                    $settings[$name] = false;
                } elseif ((ifset($row['control_type']) == waHtmlControl::GROUPBOX) && !empty($row['value'])) {
                    $settings[$name] = array();
                } elseif (!empty($row['control_type']) || isset($row['value'])) {
                    $this->settings[$this->env][$name] = isset($row['value']) ? $row['value'] : null;
                    unset($data[$name]);
                }
            }
        }

        foreach ($settings as $name => $value) {
            $this->settings[$this->env][$name] = $value;
            // save to db
            $data[$name] = is_array($value) ? json_encode($value) : $value;
        }
        self::getSettingsModel()->set('webasyst', $this->getSettingsKey(), json_encode($data));
    }

    /**
     * @return array
     */
    public function getLocale()
    {
        return array();
    }

    /**
     * @param array|string $address
     * @param string array $options
     * @return string
     */
    abstract protected function getByAddress($address, $options = array());

    /**
     * @param float $lat
     * @param float $lng
     * @param float array $options
     * @return string
     */
    abstract protected function getByLatLng($lat, $lng, $options = array());

    /**
     * @param string $env
     */
    public function setEnvironment($env = self::FRONTEND_ENVIRONMENT)
    {
        $this->env = $env === self::FRONTEND_ENVIRONMENT ? self::FRONTEND_ENVIRONMENT : self::BACKEND_ENVIRONMENT;
    }

    public function getEnvironment()
    {
        return $this->env;
    }
}
