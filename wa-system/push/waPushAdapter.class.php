<?php

abstract class waPushAdapter
{
    protected $controls = array();
    protected $settings = null;

    //
    // Init methods
    //

    /**
     * @return string
     */
    public function getId()
    {
        $class = get_class($this);
        return mb_strtolower(substr($class, 0, -4));
    }

    /**
     * @return string
     */
    abstract public function getName();

    public function isEnabled()
    {
        return false;
    }

    /**
     * Return JS, which launches a subscribe on push-notifications
     * @return string
     */
    abstract public function getInitJs();

    abstract protected function initControls();

    //
    // Dispatch actions
    //

    public function dispatch($action)
    {

    }

    public function getActionUrl($action = null, $absolute = false)
    {
        $root_url = wa()->getRootUrl($absolute);
        return $root_url.'push.php/'.$this->getId().'/'.$action;
    }

    //
    // Setup methods
    //

    /**
     * Called when saving settings to configure
     * on the service side using saved keys and tokens
     */
    public function setup()
    {
        // override it in adapter if needed
    }

    /**
     * Sending push notifications to subscribers
     * @param int|array $id — list of push subscriber ids who need to send a notification
     * @param array $data data for push-notification (title, message, url, image_url, etc..)
     */
    abstract public function send($id, $data);

    /**
     * Sending push notifications to contacts
     * @param int|array $contact_id — list of contact ids who need to send a notification
     * @param array $data data for push-notification (title, message, url, image_url, etc..)
     */
    abstract public function sendByContact($contact_id, $data);

    /**
     * Json manifest content
     * @return array
     */
    public function getManifest()
    {
        // override it in adapter if needed
        return array();
    }

    //
    // Settings methods
    //

    /**
     * @param null|string $name The name of the setting (optional). If not filled, returns the entire list of adapter settings.
     * @return null|string|array
     */
    public function getSettings($name = null)
    {
        if ($this->settings === null) {
            $settings = $this->getSettingsModel()->get('webasyst', $this->getSettingsKey(), '{}');
            $this->settings = json_decode($settings, true);
            foreach ($this->settings as $key => $value) {
                // decode non string values
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
     * Returns html with adapter settings controls.
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function getSettingsHtml($params = array())
    {
        waHtmlControl::addNamespace($params, $this->getId());
        $this->initControls();
        $controls = array();
        $default = array(
            'instance'            => & $this,
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><div class="hint">%s</div>',
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
            // Encode array values
            $data[$name] = is_array($value) ? json_encode($value) : $value;
        }

        $this->getSettingsModel()->set('webasyst', $this->getSettingsKey(), json_encode($data));
    }

    /**
     * @return string
     */
    protected function getSettingsKey()
    {
        return sprintf('push_adapter_%s', $this->getId());
    }

    /**
     * @return waAppSettingsModel
     */
    protected static function getSettingsModel()
    {
        static $app_settings_model;

        if (!$app_settings_model) {
            $app_settings_model = new waAppSettingsModel();
        }
        return $app_settings_model;
    }

    /**
     * @return waPushSubscribersModel
     */
    protected static function getPushSubscribersModel()
    {
        static $push_subscribers_model;

        if (!$push_subscribers_model) {
            $push_subscribers_model = new waPushSubscribersModel();
        }
        return $push_subscribers_model;
    }
}