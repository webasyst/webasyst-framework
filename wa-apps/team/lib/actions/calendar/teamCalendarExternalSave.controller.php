<?php

class teamCalendarExternalSaveController extends waJsonController
{
    public function execute()
    {
        $cem = new teamCalendarExternalModel();

        $id = $this->getId();
        if ($id <= 0) {
            $id = $cem->add(array(
                'type' => $this->getType()
            ));
        } else {
            $calendar = $cem->getById($id);
            if (!$calendar) {
                throw new waException(_w('Calendar not found'));
            }
            $external_calendar = $this->getExternalCalendar();
            $update = array(
                'calendar_id' => $this->getInnerCalendar(),
                'native_calendar_id' => $external_calendar['id'],
                'name' => $external_calendar['name'],
                'integration_level' => $this->getIntegrationLevel($calendar)
            );
            unset($external_calendar['id'], $external_calendar['name']);
            $cem->update($id, $update + $external_calendar);
        }

        $calendar = $cem->getById($id);

        $this->response = array(
            'calendar' => $calendar
        );
    }

    public function getType()
    {
        return $this->getRequest()->post('type');
    }

    public function getId()
    {
        return (int) $this->getRequest()->get('id');
    }

    public function getInnerCalendar()
    {
        $calendar_id = (int) $this->getRequest()->post('inner_calendar');
        $tcm = new teamWaContactCalendarsModel();
        $calendar = $tcm->getCalendar($calendar_id);
        if (!$calendar || empty($calendar['can_edit'])) {
            throw new waRightsException('Access denied');
        }
        return $calendar['id'];
    }

    public function getExternalCalendar()
    {
        $calendar = (array) $this->getRequest()->post('external_calendar');
        $calendar['id'] = (string) ifset($calendar['id'], '');
        $calendar['name'] = (string) ifset($calendar['name'], '');
        return $calendar;
    }

    public function getIntegrationLevel($calendar)
    {
        $integration_level = (string) $this->getRequest()->post('integration_level');
        $levels = teamCalendarExternalModel::getIntegrationLevels();
        if (!in_array($integration_level, $levels)) {
            $integration_level == teamCalendarExternalModel::INTEGRATION_LEVEL_SUBSCRIPTION;
        }

        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
        if (!$plugin) {
            throw new waException(_w("External calendar plugin not found"));
        }

        $plugin_level = $plugin->getIntegrationLevel();

        $enable = array();
        foreach ($levels as $level) {
            $enable[$level] = true;
            if ($plugin_level == $level) {
                break;
            }
        }

        if (empty($enable[$integration_level])) {
            throw new waException(_w('Not enabled level of integration'));
        }
        return $integration_level;
    }
}
