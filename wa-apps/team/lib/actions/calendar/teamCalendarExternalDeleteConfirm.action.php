<?php

class teamCalendarExternalDeleteConfirmAction extends teamContentViewAction
{
    public function execute()
    {
        $calendar = $this->getCalendar();
        if (!$this->canDelete($calendar)) {
            throw new waRightsException(_w('Access denied'));
        }
        $this->view->assign(array(
            'calendar' => $calendar,
            'events_count' => $this->countEvents($calendar)
        ));
    }

    public function canDelete($calendar)
    {
        return $calendar['is_own'] || wa()->getUser()->isAdmin('team');
    }

    public function getCalendar()
    {
        $id = (int) $this->getRequest()->get('id');
        $cem = new teamCalendarExternalModel();
        $calendar = $cem->getCalendar($id);
        if (!$calendar) {
            throw new waException(_w('External calendar not found'));
        }
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
        $calendar['name'] = $plugin->getCalendarName();
        $calendar['is_connected'] = $plugin->isConnected();
        $calendar['is_own'] = $calendar['contact_id'] == wa()->getUser()->getId();
        return $calendar;
    }

    public function countEvents($calendar)
    {
        $m = new teamEventExternalModel();
        return $m->count($calendar['id']);
    }
}
