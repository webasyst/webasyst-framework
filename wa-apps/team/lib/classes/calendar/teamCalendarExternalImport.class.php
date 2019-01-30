<?php

class teamCalendarExternalImport
{
    /**
     * @var teamEventExternalModel
     */
    protected $eem;

    /**
     * @var teamCalendarExternal
     */
    protected $calendar;

    /**
     * @param int|array|teamCalendarExternal $calendar
     * @param array $events
     */
    public static function importEvents($calendar, array $events)
    {
        $import = new self($calendar);
        $import->import($events);
    }

    /**
     * teamExternalCalendarImport constructor.
     * @param int|array|teamCalendarExternal $calendar
     */
    public function __construct($calendar)
    {
        if (!($calendar instanceof teamCalendarExternal)) {
            $calendar = new teamCalendarExternal($calendar);
        }
        $this->calendar = $calendar;
    }

    /**
     * @param array $events
     */
    public function import(array $events)
    {
        $count = count($events);
        if ($count <= 0) {
            return;
        }

        $calendar_id = $this->calendar->getId();

        $event_model = $this->getEventModel();
        foreach ($events as $event) {
            $original_event = $event_model->getByCalendarAndNativeId(array($calendar_id, $event['native_event_id']));
            if (empty($original_event)) {
                $event_model->add($calendar_id, $event);
            } else {
                $event['calendar_external_id'] = $calendar_id;
                $event_model->update($event);
            }
        }
    }

    protected function getEventModel()
    {
        if (!$this->eem) {
            $this->eem = new teamEventExternalModel();
        }
        return $this->eem;
    }
}
