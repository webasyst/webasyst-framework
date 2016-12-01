<?php

class teamCalendarDeleteConfirmAction extends teamContentViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }
        $calendar_id = waRequest::request('id', null, waRequest::TYPE_INT);
        $ccm = new teamWaContactCalendarsModel();
        $calendar = $ccm->getById($calendar_id);
        if (!$calendar_id || !$calendar) {
            throw new waException('Calendar not found');
        }
        $this->view->assign(array(
            'calendar' => $calendar,
            'count_events' => $ccm->countEvents($calendar['id']),
            'count_external_calendars' => $ccm->countExternalCalendars($calendar['id'])
        ));
    }
}
