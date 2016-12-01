<?php

class teamCalendarExternalDeleteController extends waJsonController
{
    public function execute()
    {
        $calendar_id = (int) $this->getRequest()->post('id');
        if (!$this->canDelete($calendar_id)) {
            throw new waRightsException(_w('Access denied'));
        }

        $m = new teamCalendarExternalModel();
        $m->delete($calendar_id, $this->getRequest()->post('with_events'));
    }

    public function canDelete($calendar_id)
    {
        $m = new teamCalendarExternalModel();
        $contact_id = $m->select('contact_id')->where('id  = ?', $calendar_id)->fetchField();
        $is_own = $contact_id == wa()->getUser()->getId() ;
        return $is_own || wa()->getUser()->isAdmin('team');
    }
}
