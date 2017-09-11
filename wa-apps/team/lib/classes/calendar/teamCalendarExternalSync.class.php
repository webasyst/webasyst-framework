<?php

class teamCalendarExternalSync
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

    /**
     * @var teamConfig
     */
    protected $config;

    protected $options;

    /**
     * @var array|null
     */
    protected $calendar;

    public function __construct($options = array())
    {
        $this->options = $options;
        $this->process_info = array();
    }

    public function execute()
    {
        if (isset($this->options['calendar_id']) && wa_is_int($this->options['calendar_id'])) {
            $this->calendar = $this->getCalendarModel()->getCalendar($this->options['calendar_id']);
        } else {
            $this->calendar = $this->getCalendarModel()->getLastSynchronized();
        }
        if ($this->calendar) {
            $this->calendar = $this->executeOne($this->calendar);
        }

        $this->deleteAbandoned();
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

    protected function executeOne($calendar)
    {
        $this->process_info[$calendar['id']] = ifset($this->process_info[$calendar['id']], array());

        $res = array();

        try {
            $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
            $options = $this->options;

            $min_start = $this->getCalendarModel()->getMinStart($this->calendar['id']);
            $today = strtotime(date('Y-m-d'));
            $options['min_time'] = date('Y-m-d', min(strtotime($min_start), $today));

            $max_time_offset = $this->getConfig()->getExternalCalendarSyncMaxDateOffset();
            $options['max_time'] = date('Y-m-d', strtotime("+ {$max_time_offset} months"));

            $res = $plugin->getChanges($options);
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

        // process change list

        $list = (array) ifset($res['change'], array());
        $event_model = $this->getEventModel();
        foreach ($list as $event) {
            $original_event = $event_model->getByCalendarAndNativeId(array($calendar['id'], $event['native_event_id']));
            if (empty($original_event)) {
                $event_model->add($calendar['id'], $event);
            } else {
                $event['calendar_external_id'] = $calendar['id'];
                $event_model->update($event);
            }

        }

        // process delete list
        $list = (array) ifset($res['delete'], array());
        if ($list) {
            $event_model->deleteByCalendarAndNativeEventId($calendar['id'], $list);
        }


        $res['info'] = ifset($res['info'], array());
        if ($res['info']) {
            $this->getCalendarParamsModel()->add($calendar['id'], $res['info']);
            $calendar['params'] = array_merge($calendar['params'], $res['info']);
        }

        $this->getCalendarModel()->updateById($calendar['id'], array(
            'synchronize_datetime' => date('Y-m-d H:i:s')
        ));

        return $calendar;
    }

    /**
     * Delete abandoned (not connected) calendars
     */
    protected function deleteAbandoned()
    {
        // 3 hour timeout
        $this->getCalendarModel()->deleteAbandoned(10800);
    }

    /**
     * @return teamCalendarExternalModel
     */
    protected function getCalendarModel()
    {
        if (!$this->cem) {
            $this->cem = new teamCalendarExternalModel();
        }
        return $this->cem;
    }

    protected function getEventModel()
    {
        if (!$this->eem) {
            $this->eem = new teamEventExternalModel();
        }
        return $this->eem;
    }

    protected function getCalendarParamsModel()
    {
        if (!$this->cepm) {
            $this->cepm = new teamCalendarExternalParamsModel();
        }
        return $this->cepm;
    }
}
