<?php

class teamCalendarExternal
{
    /**
     * @var teamCalendarExternalModel
     */
    private static $calendar_model;

    /**
     * @var teamCalendarExternalParamsModel
     */
    private static $calendar_params_model;

    protected $info;

    public function __construct($calendar_external)
    {
        $this->info = $this->typecastCalendarExternal($calendar_external);
    }

    public function getId()
    {
        return $this->info['id'];
    }

    public function getType()
    {
        return $this->info['type'];
    }

    public function getName()
    {
        return $this->info['name'];
    }

    public function getContactId()
    {
        return $this->info['contact_id'];
    }

    public function getCreateDatetime()
    {
        return $this->info['create_datetime'];
    }

    public function getCalendarId()
    {
        return $this->info['calendar_id'];
    }

    public function getNativeCalendarId()
    {
        return $this->info['native_calendar_id'];
    }

    public function getIntegrationLevel()
    {
        $levels = teamCalendarExternalModel::getIntegrationLevels();
        $level = $this->info['integration_level'] = ifset($this->info['integration_level']);
        if (!in_array($level, $levels)) {
            $level = teamCalendarExternalModel::INTEGRATION_LEVEL_SUBSCRIPTION;
        }
        return $level;
    }

    public function getSynchronizeDatetime()
    {
        return $this->info['synchronize_datetime'];
    }

    public function setParam($name, $value)
    {
        $this->setParams(array($name => $value));
    }

    public function setParams($params)
    {
        $set = array();
        $delete = array();
        foreach ($params as $name => $value) {
            if ($value === null) {
                if (array_key_exists($name, $this->info['params'])) {
                    unset($this->info['params'][$name]);
                }
                $delete[] = $name;
            } else {
                $this->info['params'][$name] = (string) $value;
                $set[$name] = $value;
            }
        }
        if ($delete) {
            $this->getCalendarParamsModel()->delete($this->info['id'], $delete);
        }
        if ($set) {
            $this->getCalendarParamsModel()->set($this->info['id'], $set);
        }
    }

    public function getParam($name)
    {
        return isset($this->info['params'][$name]) ? $this->info['params'][$name] : null;
    }

    public function getParams()
    {
        return $this->info['params'];
    }

    public function deleteParam($name)
    {
        $this->setParams(array($name => null));
    }

    public function deleteParams($names)
    {
        $this->setParams(array_fill_keys($names, null));
    }

    public function toArray()
    {
        return $this->info;
    }

    protected function typecastCalendarExternal($calendar_external)
    {
        $m = $this->getCalendarModel();
        if (!is_array($calendar_external)) {
            $calendar_external_id = (int) $calendar_external;
            $calendar_external = $m->getCalendar($calendar_external_id);
        }
        $fields = array_keys($m->getMetadata());
        foreach ($fields as $field) {
            $calendar_external[$field] = ifset($calendar_external[$field], '');
        }
        if (!isset($calendar_external['params'])) {
            $calendar_external['params'] = array();
        }
        $calendar_external['params'] = (array) $calendar_external['params'];

        return $calendar_external;
    }

    private function getCalendarModel()
    {
        if (!self::$calendar_model) {
            self::$calendar_model = new teamCalendarExternalModel();
        }
        return self::$calendar_model;
    }

    private function getCalendarParamsModel()
    {
        if (!self::$calendar_params_model) {
            self::$calendar_params_model = new teamCalendarExternalParamsModel();
        }
        return self::$calendar_params_model;
    }
}
