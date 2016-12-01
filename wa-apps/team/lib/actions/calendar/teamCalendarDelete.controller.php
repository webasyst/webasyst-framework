<?php
class teamCalendarDeleteController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }
        $calendar_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $ccm = new teamWaContactCalendarsModel();
        $calendar = $ccm->getById($calendar_id);
        if (!$calendar_id || !$calendar) {
            throw new waException('Calendar not found');
        }
        $ccm->deleteCalendar($calendar_id);
        $this->logAction('calendar_delete', $calendar_id);
    }
}
