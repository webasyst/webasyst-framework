<?php

class teamCalendarExternalPull
{
    /**
     * @var teamCalendarExternalModel
     */
    protected $cem;

    /**
     * @var teamEventExternalModel
     */
    protected $eem;

    /**
     * @var teamCalendarExternalParamsModel
     */
    protected $cepm;

    protected $options;

    /**
     * @var teamConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $process_info;

    /**
     * @var array|null
     */
    protected $calendar;

    /**
     * @var teamCalendarExternalImport
     */
    protected $import;

    /**
     * teamCalendarExternalPull constructor.
     * @param int $external_calendar_id
     * @param array $options
     * @throws waException
     */
    public function __construct($external_calendar_id, $options = array())
    {
        $this->calendar = $this->getCalendarModel()->getCalendar($external_calendar_id);
        if (!$this->calendar) {
            throw new waException('Calendar not found');
        }
        $this->options = $options;
        $this->process_info = array();
        $this->import = new teamCalendarExternalImport($this->calendar);
    }

    /**
     * @return teamConfig
     */
    public function getConfig()
    {
        if (!$this->config) {
            $this->config = wa('team')->getConfig();
        }
        return $this->config;
    }

    public function execute()
    {
        $calendar = $this->calendar;
        if (!$calendar) {
            return;
        }

        $res = array();

        try {
            $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
            $options = $this->options;
            $options['min_time'] = date('Y-m-d');
            $max_time_offset = $this->getConfig()->getExternalCalendarSyncMaxDateOffset();
            $options['max_time'] = date('Y-m-d', strtotime("+ {$max_time_offset} months"));
            $res = $plugin->getEvents($options);
        } catch (Exception $e) {
            // just ignore for now
            $this->getCalendarModel()->updateById($calendar['id'], array(
                'synchronize_datetime' => date('Y-m-d H:i:s')
            ));
            if (waSystemConfig::isDebug()) {
                $message = join(PHP_EOL, array(
                    $e->getMessage(),
                    $e->getTraceAsString(),
                    'Calendar: ',
                    var_export($calendar, true)
                ));
                waLog::log($message, 'team/class/' . get_class($this) . '.log');
            }
        }

        $list = ifset($res['list'], array());

        $this->import->import($list);
        unset($res['list']);

        $res['info'] = ifset($res['info'], array());
        if ($res['info']) {
            $this->process_info = array_merge($this->process_info, $res['info']);
        }

        if (isset($res['info']['done'])) {
            $this->getCalendarModel()->updateById($calendar['id'], array(
                'synchronize_datetime' => date('Y-m-d H:i:s')
            ));
            unset($res['info']['done']);
        }

        $this->getCalendarParamsModel()->add($calendar['id'], $res['info']);

        $calendar['params'] = array_merge($calendar['params'], $res['info']);
    }

    public function getProcessInfo()
    {
        return $this->process_info;
    }

    /**
     * @return teamCalendarExternalModel
     */
    public function getCalendarModel()
    {
        if (!$this->cem) {
            $this->cem = new teamCalendarExternalModel();
        }
        return $this->cem;
    }

    protected function getCalendarParamsModel()
    {
        if (!$this->cepm) {
            $this->cepm = new teamCalendarExternalParamsModel();
        }
        return $this->cepm;
    }
}
