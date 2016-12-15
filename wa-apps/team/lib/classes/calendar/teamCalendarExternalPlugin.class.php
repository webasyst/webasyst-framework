<?php

abstract class teamCalendarExternalPlugin extends waPlugin
{
    /**
     * @var teamCalendarExternal
     */
    protected $calendar;

    /**
     * @var teamCalendarExternalModel
     */
    private static $cem;

    /**
     * @param $plugin_id
     * @return null|teamCalendarExternalPlugin
     */
    public static function factory($plugin_id, $set_active = false)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $plugins = self::getPlugins();
        if (!isset($plugins[$plugin_id])) {
            self::getCalendarExternalModel()->deleteByType($plugin_id);
            return new teamCalendarExternalNullPlugin($plugin_id);
        }
        try {
            $plugin = wa('team')->getPlugin($plugin_id, $set_active);
            if (!($plugin instanceof teamCalendarExternalPlugin)) {
                return null;
            }
        } catch (waException $e) {
            return null;
        }
        return $plugin;
    }

    /**
     * @param int|array|teamCalendarExternal $calendar_external
     * @return teamCalendarExternalPlugin|null
     */
    public static function factoryByCalendar($calendar_external)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if (wa_is_int($calendar_external)) {
            $calendar_external = self::getCalendarExternalModel()->getCalendar($calendar_external);
        }
        if (!$calendar_external) {
            return null;
        }
        if (is_array($calendar_external) && !empty($calendar_external) && isset($calendar_external['type'])) {
            $calendar_external = new teamCalendarExternal($calendar_external);
        }
        if (!($calendar_external instanceof teamCalendarExternal)) {
            return null;
        }
        $plugin = self::factory($calendar_external->getType());
        if (!$plugin) {
            return null;
        }
        $plugin->setCalendar($calendar_external);
        return $plugin;
    }

    public static function getPlugins()
    {
        if (waConfig::get('is_template')) {
            return array();
        }

        static $plugins;
        if ($plugins === null) {
            $plugins = wa('team')->getConfig()->getPlugins();
            foreach ($plugins as $id => $plugin) {
                if (empty($plugin['external_calendar'])) {
                    unset($plugins[$id]);
                }
            }
        }

        foreach ($plugins as $id => &$plugin) {
            $plugin['icon16_url'] = wa()->getAppStaticUrl('team', true) . 'plugins/' . $id . '/' . $plugin['icon'];
        }
        unset($plugin);

        return $plugins;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param teamCalendarExternal $calendar
     */
    public function setCalendar(teamCalendarExternal $calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * @return teamCalendarExternal
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    public function hasSettings()
    {
        return count($this->getSettingsConfig()) > 0;
    }

    public function __get($name)
    {
        return $this->getSettings($name);
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        if (!isset($this->info['icon16_url'])) {
            $this->info['icon16_url'] = $this->getPluginStaticUrl(true) . $this->info['icon'];
        }
        return $this->info['icon16_url'];
    }

    public static function getCallbackUrlById($id)
    {
        return teamHelper::getAbsoluteUrl() . "calendar/external/authorize/{$id}";
    }

    public function getCalendarName()
    {
        $name = '';
        if ($this->calendar instanceof teamCalendarExternal) {
            $name = $this->calendar->getName();
        }
        if (strlen($name) > 0) {
            return $name;
        }
        return $this->getName();
    }

    public function isMapped()
    {
        if (!$this->checkCalendar()) {
            return false;
        }
        return $this->calendar->getNativeCalendarId() && $this->calendar->getCalendarId() > 0;
    }

    public function getIntegrationLevel()
    {
        $level = $this->info['integration_level'] = ifset($this->info['integration_level']);
        $levels = teamCalendarExternalModel::getIntegrationLevels();
        if (!in_array($level, $levels)) {
            $level = teamCalendarExternalModel::INTEGRATION_LEVEL_SUBSCRIPTION;
        }
        return $level;
    }

    /**
     * @param $id
     * @param array $options
     * @return string
     */
    abstract public function authorizeBegin($id, $options = array());

    /**
     * @param array $options
     * @return array
     */
    abstract public function authorizeEnd($options = array());

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param array $options
     * @return array
     */
    abstract public function getCalendars($options = array());

    /**
     * @param array $options
     * @return array|false
     */
    abstract public function getEvents($options = array());

    /**
     * @return bool
     */
    abstract public function isImported();

    /**
     * @param array $options
     * @return array|false
     */
    abstract public function getChanges($options = array());

    /**
     * @return mixed
     */
    abstract public function isConnected();

    /**
     * @param array $options
     * @return string
     */
    abstract public function getAccountInfoHtml($options = array());

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param $event
     * @param array $options
     * @return bool
     */
    public function addEvent($event, $options = array())
    {
        // override it if needed
        return false;
    }

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param $event
     * @param array $options
     * @return bool|array
     */
    public function updateEvent($event, $options = array())
    {
        // override it if needed
        return false;
    }

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param $event
     * @param array $options
     * @return bool
     */
    public function deleteEvent($event, $options = array())
    {
        // override it if needed
        return false;
    }

    /**
     * @return bool
     */
    public function areAllRequiredSettingsFilled()
    {
        $config = $this->getSettingsConfig();
        $settings = $this->getSettings();
        foreach ($config as $key => $options) {
            if (!empty($options['required']) && empty($settings[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param bool $throw
     * @throws waException
     * @return bool
     */
    protected function checkCalendar($throw = false)
    {
        if (!($this->calendar instanceof teamCalendarExternal)) {
            if ($throw) {
                throw new waException('teamCalendarExternalPlugin instance must be initialized with teamCalendarExternal instance');
            }
            return false;
        }
        return true;
    }

    /**
     * @return teamCalendarExternalModel
     */
    protected static function getCalendarExternalModel()
    {
        if (!self::$cem) {
            self::$cem = new teamCalendarExternalModel();
        }
        return self::$cem;
    }

    public function uninstall($force = false)
    {
        self::getCalendarExternalModel()->deleteByType($this->id);
        return parent::uninstall($force);
    }
}
