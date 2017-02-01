<?php

abstract class waMapAdapter
{
    protected $controls = array();
    protected $settings = null;

    /**
     * @var waAppSettingsModel
     */
    protected static $app_settings_model;

    /**
     * @param string|array $address - string or array(LAT, LNG)
     * @param array $options - map options
     *             'width' => '50%',
     *             'height' => '200px',
     *             'zoom' => 12
     * @return string
     */
    public function getHTML($address, $options = array())
    {
        if (!$address) {
            return '';
        }
        if (is_string($address)) {
            return $this->getByAddress($address, $options);
        } elseif (is_array($address) && isset($address[0]) && isset($address[1])) {
            return $this->getByLatLng($address[0], $address[1], $options);
        }
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
            'description_wrapper' => '<br><span class="hint">%s</span>',
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
        return sprintf('map_adapter_%s', $this->getId());
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
        if ($this->settings === null) {
            $settings = self::getSettingsModel()->get('webasyst', $this->getSettingsKey(), '{}');
            $this->settings = @json_decode($settings, true);
            foreach ($this->settings as $key => $value) {
                #decode non string values
                if (!is_numeric($value)) {
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$key] = $json;
                    }
                }
            }

            $this->initControls();
            foreach ($this->controls as $key => $row) {
                if (!isset($this->settings[$key])) {
                    $this->settings[$key] = is_array($row) ? (isset($row['value']) ? $row['value'] : null) : $row;
                }
            }
        }
        if ($name === null) {
            return $this->settings;
        } else {
            return isset($this->settings[$name]) ? $this->settings[$name] : null;
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
                    $this->settings[$name] = isset($row['value']) ? $row['value'] : null;
                    unset($data[$name]);
                }
            }
        }

        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
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
}
