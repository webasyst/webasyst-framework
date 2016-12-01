<?php

class teamIcsPluginBackendImportController extends waJsonController
{
    public function execute()
    {
        $current_contact_id = (int) $this->getRequest()->post('contact_id');
        $is_own = $current_contact_id == wa()->getUser()->getId();
        if (!$is_own) {
            throw new waRightsException(_w('Access denied'));
        }

        $inner_calendar_id = (int) $this->getRequest()->post('inner_calendar_id');
        if ($inner_calendar_id <= 0) {
            $this->setError(_w("Choose calendar"), 'inner_calendar_id');
        }

        $tcm = new teamWaContactCalendarsModel();
        $inner_calendar = $tcm->getCalendar($inner_calendar_id);
        if (!$inner_calendar || empty($inner_calendar['can_edit'])) {
            throw new waRightsException('Access denied');
        }

        $files = $this->getRequest()->file('file');
        if (count($files) <= 0) {
            return $this->setError(_w("No .ics file specified"), 'file');
        }
        /**
         * @var waRequestFile $file
         */
        $file = $files[0];
        if (!$file->uploaded()) {
            return $this->setError(_w("No .ics file specified"), 'file');
        }


        $filename = md5(join('_', array(__CLASS__, __METHOD__, $current_contact_id)));
        $filename = $filename . '.ics';
        $dirpath = wa()->getTempPath('plugins/ics/', 'team');
        waFiles::create($dirpath, true);
        $file->copyTo($dirpath, $filename);

        $parser = new teamIcsCalendarParser($dirpath . $filename);
        $events = $parser->getEvents();

        if (!$events) {
            return $this->setError(_w("There are no events in the file"));
        }

        $calendar_name = $parser->getField('X-WR-CALNAME');
        if (strlen($calendar_name) <= 0) {
            $calendar_name = $parser->getField('PRODID');
        }

        $calendar_external = $this->createCalendar($calendar_name, $inner_calendar_id);
        if (!$calendar_external || !$calendar_external->getId()) {
            return $this->setError(_w("Can't create external calendar"));
        }

        teamCalendarExternalImport::importEvents($calendar_external, $events);
    }

    public function createCalendar($name, $inner_calendar_id)
    {
        $cem = new teamCalendarExternalModel();
        $id = $cem->add(array(
            'type' => 'ics',
            'name' => $name,
            'calendar_id' => $inner_calendar_id,
            'native_calendar_id' => teamIcsPlugin::NATIVE_CALENDAR_ID_IMPORTED_FROM_FILE
        ));
        return new teamCalendarExternal($id);
    }
}